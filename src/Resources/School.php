<?php

declare(strict_types=1);

namespace Nexora\Sdk\Resources;

use Nexora\Sdk\Http\HttpClient;
use Nexora\Sdk\Http\RequestOptions;

/**
 * School sector resource for the Nexora Developer API.
 *
 * Provides access to school-specific endpoints: students, classes, attendance.
 */
final class School
{
    public function __construct(
        private readonly HttpClient $client
    ) {
    }

    /**
     * List students with optional filters.
     *
     * @param array<string, mixed> $query Filter parameters (page, limit, search, status, classId)
     * @return array<string, mixed>
     */
    public function listStudents(array $query = []): array
    {
        return $this->client->get('/school/students', $query);
    }

    /**
     * Create a new student record.
     *
     * @param array<string, mixed> $data Student attributes
     * @return array<string, mixed>
     */
    public function createStudent(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/school/students', $data, new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    /**
     * List attendance records.
     *
     * @param array<string, mixed> $query Filter parameters (page, limit, classId, date)
     * @return array<string, mixed>
     */
    public function listAttendance(array $query = []): array
    {
        return $this->client->get('/school/attendance', $query);
    }

    /**
     * Record attendance.
     *
     * @param array<string, mixed> $data Attendance data
     * @return array<string, mixed>
     */
    public function recordAttendance(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/school/attendance', $data, new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    /**
     * List school classes.
     *
     * @param array<string, mixed> $query Pagination parameters (page, limit)
     * @return array<string, mixed>
     */
    public function listClasses(array $query = []): array
    {
        return $this->client->get('/school/classes', $query);
    }

    /**
     * Create a new class.
     *
     * @param array<string, mixed> $data Class attributes
     * @return array<string, mixed>
     */
    public function createClass(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/school/classes', $data, new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    /**
     * List teachers (requires the teachers:read scope).
     *
     * @param array<string, mixed> $query page, limit
     * @return array<string, mixed>
     */
    public function listTeachers(array $query = []): array
    {
        return $this->client->get('/school/teachers', $query);
    }

    /**
     * List published results only (requires the results:read scope).
     *
     * @param array<string, mixed> $query page, limit, academicSession, term, studentId
     * @return array<string, mixed>
     */
    public function listResults(array $query = []): array
    {
        return $this->client->get('/school/results', $query);
    }

    /**
     * List school fee payments (requires the payments:read scope).
     *
     * @param array<string, mixed> $query page, limit, status, academicSession, term
     * @return array<string, mixed>
     */
    public function listPayments(array $query = []): array
    {
        return $this->client->get('/school/payments', $query);
    }
}
