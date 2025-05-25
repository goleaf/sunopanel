<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseApiController extends Controller
{
    /**
     * Default pagination limit.
     */
    protected int $defaultLimit = 20;

    /**
     * Maximum pagination limit.
     */
    protected int $maxLimit = 100;

    /**
     * Return a success response.
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Return an error response.
     */
    protected function error(
        string $message = 'An error occurred',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Return a paginated response.
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
                'path' => $paginator->path(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
        ], $message);
    }

    /**
     * Return a collection response.
     */
    protected function collection(
        Collection $collection,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        return $this->success([
            'items' => $collection->values(),
            'count' => $collection->count(),
        ], $message);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return a validation error response.
     */
    protected function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a server error response.
     */
    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Get pagination parameters from request.
     */
    protected function getPaginationParams(Request $request): array
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = min(
            $this->maxLimit,
            max(1, (int) $request->get('limit', $this->defaultLimit))
        );

        return compact('page', 'limit');
    }

    /**
     * Get sorting parameters from request.
     */
    protected function getSortingParams(Request $request, array $allowedFields = []): array
    {
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = strtolower($request->get('sort_order', 'desc'));

        // Validate sort field
        if (!empty($allowedFields) && !in_array($sortBy, $allowedFields)) {
            $sortBy = $allowedFields[0] ?? 'id';
        }

        // Validate sort order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        return compact('sortBy', 'sortOrder');
    }

    /**
     * Get filtering parameters from request.
     */
    protected function getFilterParams(Request $request, array $allowedFilters = []): array
    {
        $filters = [];

        foreach ($allowedFilters as $filter) {
            if ($request->has($filter)) {
                $filters[$filter] = $request->get($filter);
            }
        }

        return $filters;
    }

    /**
     * Handle exceptions and return appropriate error response.
     */
    protected function handleException(\Throwable $e): JsonResponse
    {
        \Log::error('API Exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (app()->environment('production')) {
            return $this->serverError('An unexpected error occurred');
        }

        return $this->serverError($e->getMessage());
    }

    /**
     * Transform model for API response.
     */
    protected function transformModel($model, array $fields = []): array
    {
        if (is_null($model)) {
            return [];
        }

        $data = $model->toArray();

        // If specific fields are requested, filter the data
        if (!empty($fields)) {
            $data = array_intersect_key($data, array_flip($fields));
        }

        // Add computed fields
        if (method_exists($model, 'getApiAttributes')) {
            $data = array_merge($data, $model->getApiAttributes());
        }

        return $data;
    }

    /**
     * Transform collection for API response.
     */
    protected function transformCollection($collection, array $fields = []): array
    {
        return $collection->map(function ($item) use ($fields) {
            return $this->transformModel($item, $fields);
        })->toArray();
    }
} 