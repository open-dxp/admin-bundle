<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\EventListener;

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\EventListener\AdminExceptionListener;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Model\Element\Editlock;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Model\Exception\ConfigWriteException;
use OpenDxp\TestFoundation\Container;
use RuntimeException;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Dispatches the exception of an XHR request to Symfony's ErrorListener and to the AdminExceptionListener.
 *
 * @return array{status: ?int, symfony: list<string>, admin: list<string>} The levels each of them logged.
 */
function handleException(Throwable $exception, string $context): array
{
    $symfonyLog = new BufferingLogger();
    $adminLog = new BufferingLogger();

    $listener = new AdminExceptionListener($adminLog);
    $listener->setOpenDxpContextResolver(Container::get(OpenDxpContextResolver::class));

    $dispatcher = new EventDispatcher();
    $dispatcher->addSubscriber(new ErrorListener(null, $symfonyLog));
    $dispatcher->addSubscriber($listener);

    $request = Request::create('/admin/object/save', 'POST', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    $request->attributes->set(OpenDxpContextResolver::ATTRIBUTE_OPENDXP_CONTEXT, $context);

    $event = new ExceptionEvent(OpenDxp::getKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    $dispatcher->dispatch($event, KernelEvents::EXCEPTION);

    return [
        'status'  => $event->getResponse()?->getStatusCode(),
        'symfony' => array_column($symfonyLog->cleanLogs(), 0),
        'admin'   => array_column($adminLog->cleanLogs(), 0),
    ];
}

it('answers an expected exception of the admin and logs it only at its own level', function (Throwable $exception, int $status, string $level) {
    expect(handleException($exception, OpenDxpContextResolver::CONTEXT_ADMIN))->toBe([
        'status'  => $status,
        'symfony' => [],
        'admin'   => [$level],
    ]);
})->with([
    'a failed validation'          => [new ValidationException('Validation failed'), 422, 'info'],
    'an element locked'            => [new ElementLockedException(1, 'object', (new Editlock())->setUserId(0)), 200, 'info'],
    'a configuration not writable' => [new ConfigWriteException(), 422, 'warning'],
    'a failed admin operation'     => [new AdminOperationFailedException('Not found'), 200, 'warning'],
]);

it('leaves any other exception of the admin to Symfony, which logs it as critical', function () {
    expect(handleException(new RuntimeException('Broken'), OpenDxpContextResolver::CONTEXT_ADMIN))->toBe([
        'status'  => 500,
        'symfony' => ['critical'],
        'admin'   => [],
    ]);
});

it('leaves a failed validation outside the admin to Symfony, which logs it as critical', function () {
    expect(handleException(new ValidationException('Validation failed'), OpenDxpContextResolver::CONTEXT_DEFAULT))->toBe([
        'status'  => null,
        'symfony' => ['critical'],
        'admin'   => [],
    ]);
});
