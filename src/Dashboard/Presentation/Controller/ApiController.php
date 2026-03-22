<?php

declare(strict_types=1);

namespace App\Dashboard\Presentation\Controller;

use App\Dashboard\Application\Command\CreateSiteRecordCommand;
use App\Dashboard\Application\Command\DeleteSiteRecordCommand;
use App\Dashboard\Application\Command\UpdateSiteRecordCommand;
use App\Dashboard\Application\Query\GetDashboardQuery;
use App\Dashboard\Domain\ValueObject\SiteStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class ApiController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus
    ) {}

    public function list(Request $request): JsonResponse
    {
        $page         = max(1, (int) $request->query->get('page', 1));
        $filterStatus = $request->query->get('status') ?: null;

        $envelope = $this->queryBus->dispatch(new GetDashboardQuery(
            filterStatus: $filterStatus,
            page:         $page,
            pageSize:     GetDashboardQuery::PAGE_SIZE,
        ));

        $result = $envelope->last(HandledStamp::class)?->getResult() ?? [
            'paginated'  => null,
            'statistics' => [],
        ];

        $paginated = $result['paginated'];

        return $this->json([
            'data' => $paginated ? array_map(
                fn ($r) => [
                    'id'             => $r->id,
                    'name'           => $r->name,
                    'url'            => $r->url,
                    'status'         => $r->status,
                    'responseTimeMs' => $r->responseTimeMs,
                    'notes'          => $r->notes,
                    'createdAt'      => $r->createdAt->format(\DateTimeInterface::ATOM),
                    'updatedAt'      => $r->updatedAt->format(\DateTimeInterface::ATOM),
                ],
                $paginated->items
            ) : [],
            'meta' => [
                'currentPage' => $paginated?->page ?? 1,
                'totalPages'  => $paginated?->totalPages() ?? 1,
                'totalCount'  => $paginated?->totalCount ?? 0,
                'pageSize'    => $paginated?->pageSize ?? GetDashboardQuery::PAGE_SIZE,
            ],
            'statistics' => $result['statistics'],
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $this->commandBus->dispatch(new CreateSiteRecordCommand(
                name:   trim($body['name']   ?? ''),
                url:    trim($body['url']    ?? ''),
                status: trim($body['status'] ?? 'pending'),
                notes:  $body['notes'] ?? null,
            ));

            return $this->json(['message' => 'Site record created.'], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException|\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $this->commandBus->dispatch(new UpdateSiteRecordCommand(
                id:     $id,
                name:   trim($body['name']   ?? ''),
                url:    trim($body['url']    ?? ''),
                status: trim($body['status'] ?? 'pending'),
                notes:  $body['notes'] ?? null,
            ));

            return $this->json(['message' => 'Site record updated.']);
        } catch (\InvalidArgumentException|\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function delete(string $id): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeleteSiteRecordCommand(id: $id));
            return $this->json(['message' => 'Site record deleted.']);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function statuses(): JsonResponse
    {
        return $this->json(['statuses' => SiteStatus::allowedValues()]);
    }
}
