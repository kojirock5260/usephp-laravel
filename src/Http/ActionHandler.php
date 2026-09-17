<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Polidog\UsePhp\Runtime\Action;
use Polidog\UsePhp\Runtime\ComponentState;
use Polidog\UsePhp\Runtime\Element;
use Polidog\UsePhp\Runtime\RenderContext;
use Polidog\UsePhp\Runtime\Renderer;
use Polidog\UsePhp\Snapshot\SnapshotVerificationException;
use Polidog\UsePhp\Storage\StorageFactory;
use Polidog\UsePhp\Storage\StorageType;
use Polidog\UsePhp\UsePHP;
use Throwable;

/**
 * Applies a usePHP form action (a `setState` posted by one of the forms the
 * component rendered) and re-renders the component.
 *
 * Snapshot storage only: the signed snapshot posted with the form is the
 * state, so nothing is kept on the server between requests. Ported from
 * polidog/usephp-bear-module's action responder.
 */
final class ActionHandler
{
    public function __construct(private readonly UsePHP $app) {}

    /**
     * @param  array<string, mixed>  $props  Props the page rendered the component with
     */
    public function handle(Request $request, string $fqcn, array $props = []): Response
    {
        $actionJson = $request->input('_usephp_action');
        $instanceId = $request->input('_usephp_component');
        $snapshotJson = $request->input('_usephp_snapshot');

        if (! is_string($actionJson) || ! is_string($instanceId)) {
            return self::reject('Not a usePHP action request');
        }

        try {
            $action = Action::fromArray(json_decode($actionJson, true, 512, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            return self::reject('Invalid action');
        }

        // The action must target the component whose snapshot we restore.
        if ($action->componentId !== null && $action->componentId !== $instanceId) {
            return self::reject('Action does not match the component');
        }
        if ($action->storageType !== null && $action->storageType !== StorageType::Snapshot) {
            return self::reject('Only Snapshot storage is supported');
        }
        if ($action->type !== 'setState') {
            return self::reject('Unsupported action type');
        }

        // A signed snapshot is the only thing tying this request to state the
        // server rendered. Without it a client could bootstrap arbitrary state.
        if (! is_string($snapshotJson) || $snapshotJson === '') {
            return self::reject('Missing snapshot');
        }

        $serializer = $this->app->getSnapshotSerializer();
        try {
            $snapshot = $serializer->deserialize($snapshotJson);
        } catch (SnapshotVerificationException) {
            return self::reject('Invalid snapshot');
        }
        if ($snapshot->getInstanceId() !== $instanceId) {
            return self::reject('Snapshot does not belong to this component');
        }

        // Restore, mutate, re-render. Same order as the BEAR responder.
        ComponentState::fromSnapshot($snapshot);
        RenderContext::setApp($this->app);
        try {
            RenderContext::beginRender();

            $state = ComponentState::getInstance($instanceId, StorageType::Snapshot);
            $state->setState((int) ($action->payload['index'] ?? 0), $action->payload['value'] ?? null);

            $element = $this->app->renderPsxComponent($fqcn, $props);

            $wrapper = self::findWrapper($element, $instanceId);
            if ($wrapper === null) {
                return self::reject('Component instance not found in '.$fqcn);
            }

            $html = $request->hasHeader('X-UsePHP-Partial')
                ? $this->renderPartial($wrapper, $instanceId)   // JS: only the inside of the wrapper
                : $this->app->renderElement($element);          // no JS: the whole component again

            return new Response(CsrfTokenInjector::inject($html), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        } finally {
            RenderContext::clearApp();
            ComponentState::clearInstances();
            StorageFactory::reset();
        }
    }

    /**
     * usephp.js replaces the wrapper's innerHTML with the response and reads
     * the new snapshot from the trailing [data-usephp-snapshot-update] field.
     */
    private function renderPartial(Element $wrapper, string $instanceId): string
    {
        $serializer = $this->app->getSnapshotSerializer();
        $renderer = new Renderer(
            $instanceId,
            $serializer,
            StorageType::Snapshot,
            deferPrefix: (string) config('usephp.defer_prefix', '/_defer'),
        );

        $inner = '';
        foreach ($wrapper->children as $child) {
            $inner .= $child instanceof Element
                ? $renderer->renderElement($child)
                : htmlspecialchars((string) $child, ENT_QUOTES, 'UTF-8');
        }

        $state = ComponentState::getInstance($instanceId, StorageType::Snapshot);
        $inner .= sprintf(
            '<input type="hidden" name="_usephp_snapshot" value="%s" data-usephp-snapshot-update />',
            htmlspecialchars($serializer->serialize($state->createSnapshot()), ENT_QUOTES, 'UTF-8'),
        );

        return $inner;
    }

    private static function findWrapper(Element $element, string $instanceId): ?Element
    {
        if ($element->type === 'div' && ($element->props['data-usephp'] ?? null) === $instanceId) {
            return $element;
        }
        foreach ($element->children as $child) {
            if ($child instanceof Element) {
                $found = self::findWrapper($child, $instanceId);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private static function reject(string $reason): Response
    {
        return new Response($reason, 400, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
