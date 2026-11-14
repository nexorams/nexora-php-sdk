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
    /**
     * List Parent Portal accounts (requires the parents:read scope).
     *
     * @param array<string, mixed> $query page, limit, search, status (active|disabled), activation (pending|activated|expired)
     * @return array<string, mixed>
     */
    public function listParents(array $query = []): array
    {
        return $this->client->get('/school/parents', $query);
    }

    /**
     * Create a parent/guardian account and optionally link children (requires parents:write).
     * Nexora e-mails the activation link; no password is returned. A known e-mail answers 409 PARENT_EXISTS
     * unless `reuseExisting` is true, in which case the students are linked to the existing account.
     *
     * @param array<string, mixed> $data firstName, lastName, email, phone, students[] {studentId, relationship, isPrimaryGuardian}, reuseExisting
     * @return array<string, mixed>
     */
    public function createParent(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/school/parents', $data, new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    /**
     * List parent-student links (requires parents:read).
     *
     * @param array<string, mixed> $query page, limit, parentId, studentId, status (ACTIVE|REVOKED)
     * @return array<string, mixed>
     */
    public function listParentLinks(array $query = []): array
    {
        return $this->client->get('/school/parents/links', $query);
    }

    /**
     * Link a student to a parent (requires parents:write).
     *
     * @param array<string, mixed> $data parentId, studentId, relationship (mother|father|guardian|other), isPrimaryGuardian, isEmergencyContact
     * @return array<string, mixed>
     */
    public function linkParentStudent(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->post('/school/parents/links', $data, new RequestOptions(idempotencyKey: $idempotencyKey));
    }

    /**
     * Revoke a parent-student link by linkId, or by parentId + studentId; the parent loses access at once (requires parents:write).
     *
     * @param array<string, mixed> $data linkId | (parentId, studentId), reason
     * @return array<string, mixed>
     */
    public function revokeParentLink(array $data): array
    {
        return $this->client->post('/school/parents/links/revoke', $data);
    }
}
