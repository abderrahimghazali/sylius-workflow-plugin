<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_ADMINISTRATION_ACCESS')]
final class ProductSearchController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->query->getString('q', ''));
        if (\strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $conn = $this->entityManager->getConnection();
        $sql = 'SELECT p.code, pt.name FROM sylius_product p LEFT JOIN sylius_product_translation pt ON pt.translatable_id = p.id AND pt.locale = :locale WHERE p.code LIKE :q OR pt.name LIKE :q LIMIT 10';

        $rows = $conn->executeQuery($sql, [
            'q' => '%' . $query . '%',
            'locale' => $request->getLocale(),
        ])->fetchAllAssociative();

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'code' => $row['code'],
                'name' => $row['name'] ?? $row['code'],
            ];
        }

        return new JsonResponse($results);
    }
}
