<?php

declare(strict_types=1);

namespace App\Dashboard\Presentation\Controller;

use App\Dashboard\Application\Command\CreateSiteRecordCommand;
use App\Dashboard\Application\Command\DeleteSiteRecordCommand;
use App\Dashboard\Application\Command\UpdateSiteRecordCommand;
use App\Dashboard\Application\Query\GetDashboardQuery;
use App\Dashboard\Domain\ValueObject\SiteStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus
    ) {}

    public function index(Request $request): Response
    {
        $filterStatus = $request->query->get('status') ?: null;
        $page         = max(1, (int) $request->query->get('page', 1));

        $envelope = $this->queryBus->dispatch(
            new GetDashboardQuery(
                filterStatus: $filterStatus,
                page:         $page,
                pageSize:     GetDashboardQuery::PAGE_SIZE,
            )
        );

        $result = $envelope->last(HandledStamp::class)?->getResult() ?? [
            'paginated'  => null,
            'statistics' => [],
        ];

        return $this->render('dashboard/index.html.twig', [
            'paginated'     => $result['paginated'],
            'statistics'    => $result['statistics'],
            'filter_status' => $filterStatus,
            'statuses'      => SiteStatus::allowedValues(),
            'current_page'  => $page,
        ]);
    }

    public function create(Request $request): Response
    {
        try {
            $this->commandBus->dispatch(new CreateSiteRecordCommand(
                name:   trim($request->request->get('name', '')),
                url:    trim($request->request->get('url', '')),
                status: trim($request->request->get('status', 'pending')),
                notes:  $request->request->get('notes') ?: null
            ));
            $this->addFlash('success', 'Site record created successfully.');
        } catch (\InvalidArgumentException|\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('dashboard_index');
    }

    public function update(string $id, Request $request): Response
    {
        try {
            $this->commandBus->dispatch(new UpdateSiteRecordCommand(
                id:     $id,
                name:   trim($request->request->get('name', '')),
                url:    trim($request->request->get('url', '')),
                status: trim($request->request->get('status', 'pending')),
                notes:  $request->request->get('notes') ?: null
            ));
            $this->addFlash('success', 'Site record updated successfully.');
        } catch (\InvalidArgumentException|\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('dashboard_index');
    }

    public function delete(string $id): Response
    {
        try {
            $this->commandBus->dispatch(new DeleteSiteRecordCommand(id: $id));
            $this->addFlash('success', 'Site record deleted.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('dashboard_index');
    }
}
