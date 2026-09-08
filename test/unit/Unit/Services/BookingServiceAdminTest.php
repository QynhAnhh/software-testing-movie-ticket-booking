<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;
use App\Models\BookingModel;

/**
 * AdminTest: Phủ sóng 100% các hàm Admin/Utility trong BookingService
 * - getAdminBookingStats, getAdminBookings, getAdminBookingDetail
 * - updateAdminBookingStatus, deleteAdminBooking
 * - normalizeAdminFilters, isValidDate (indirect via filters)
 */
class BookingServiceAdminTest extends TestCase
{
    private function makeBookingModel(): BookingModel
    {
        return $this->createMock(BookingModel::class);
    }

    private function makeService(?BookingModel $bm = null): BookingService
    {
        return new BookingService($bm ?? $this->makeBookingModel());
    }

    // =========================================================================
    // getAdminBookingStats
    // =========================================================================
    public function testGetAdminBookingStatsDelegatesToModel(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingStats')->willReturn(['total' => 42, 'pending' => 5]);
        $result = $this->makeService($bm)->getAdminBookingStats();
        $this->assertSame(42, $result['total']);
    }

    // =========================================================================
    // getAdminBookings
    // =========================================================================
    public function testGetAdminBookingsWithEmptyInput(): void
    {
        $bm = $this->makeBookingModel();
        $bm->expects($this->once())->method('getAdminBookings')
            ->with(['status' => '', 'from_date' => '', 'to_date' => '', 'search' => ''])
            ->willReturn([]);
        $result = $this->makeService($bm)->getAdminBookings([]);
        $this->assertSame([], $result);
    }

    public function testGetAdminBookingsWithValidStatusFilter(): void
    {
        $bm = $this->makeBookingModel();
        $bm->expects($this->once())->method('getAdminBookings')
            ->with($this->callback(fn($f) => $f['status'] === 'paid'))
            ->willReturn([['id' => 1]]);
        $result = $this->makeService($bm)->getAdminBookings(['status' => 'paid']);
        $this->assertCount(1, $result);
    }

    // =========================================================================
    // getAdminBookingDetail
    // =========================================================================
    public function testGetAdminBookingDetailReturnsNullForInvalidId(): void
    {
        $this->assertNull($this->makeService()->getAdminBookingDetail(0));
        $this->assertNull($this->makeService()->getAdminBookingDetail(-1));
    }

    public function testGetAdminBookingDetailReturnsNullWhenNotFound(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingDetail')->willReturn(null);
        $this->assertNull($this->makeService($bm)->getAdminBookingDetail(1));
    }

    public function testGetAdminBookingDetailReturnsDataWhenFound(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingDetail')->willReturn(['id' => 1, 'status' => 'paid']);
        $bm->method('getAdminBookingTickets')->willReturn([['ticket_id' => 10]]);
        $result = $this->makeService($bm)->getAdminBookingDetail(1);
        $this->assertArrayHasKey('booking', $result);
        $this->assertArrayHasKey('tickets', $result);
        $this->assertCount(1, $result['tickets']);
    }

    // =========================================================================
    // updateAdminBookingStatus
    // =========================================================================
    public function testUpdateAdminStatusRejectsInvalidBookingId(): void
    {
        $result = $this->makeService()->updateAdminBookingStatus(0, 'paid');
        $this->assertSame('error', $result['status']);
    }

    public function testUpdateAdminStatusRejectsInvalidStatus(): void
    {
        $bm = $this->makeBookingModel();
        $result = $this->makeService($bm)->updateAdminBookingStatus(1, 'invalid_status');
        $this->assertSame('error', $result['status']);
    }

    public function testUpdateAdminStatusReturnsErrorWhenBookingNotFound(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(null);
        $result = $this->makeService($bm)->updateAdminBookingStatus(999, 'paid');
        $this->assertSame('error', $result['status']);
    }

    public function testUpdateAdminStatusSucceeds(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(['id' => 1, 'status' => 'pending']);
        $bm->method('updateBookingStatus')->willReturn(true);
        $bm->method('updateTicketsStatusByBooking')->willReturn(true);
        $result = $this->makeService($bm)->updateAdminBookingStatus(1, 'paid');
        $this->assertSame('success', $result['status']);
    }

    public function testUpdateAdminStatusToCanceled(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(['id' => 1, 'status' => 'pending']);
        $bm->method('updateBookingStatus')->willReturn(true);
        $bm->method('updateTicketsStatusByBooking')->willReturn(true);
        $result = $this->makeService($bm)->updateAdminBookingStatus(1, 'canceled');
        $this->assertSame('success', $result['status']);
    }

    public function testUpdateAdminStatusBlocksRestoreWhenSeatConflict(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(['id' => 1, 'status' => 'canceled']);
        $bm->method('hasSeatConflictWhenRestoring')->willReturn(true);
        $result = $this->makeService($bm)->updateAdminBookingStatus(1, 'paid');
        $this->assertSame('error', $result['status']);
    }

    public function testUpdateAdminStatusRollsBackWhenUpdateFails(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(['id' => 1, 'status' => 'pending']);
        $bm->method('updateBookingStatus')->willReturn(false);
        $bm->method('getError')->willReturn('DB error');
        $result = $this->makeService($bm)->updateAdminBookingStatus(1, 'paid');
        $this->assertSame('error', $result['status']);
    }

    public function testUpdateAdminStatusRollsBackWhenTicketUpdateFails(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(['id' => 1, 'status' => 'pending']);
        $bm->method('updateBookingStatus')->willReturn(true);
        $bm->method('updateTicketsStatusByBooking')->willReturn(false);
        $bm->method('getError')->willReturn('ticket error');
        $result = $this->makeService($bm)->updateAdminBookingStatus(1, 'paid');
        $this->assertSame('error', $result['status']);
    }

    // =========================================================================
    // deleteAdminBooking
    // =========================================================================
    public function testDeleteAdminBookingRejectsInvalidId(): void
    {
        $result = $this->makeService()->deleteAdminBooking(0);
        $this->assertSame('error', $result['status']);
    }

    public function testDeleteAdminBookingReturnsErrorWhenNotFound(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(null);
        $result = $this->makeService($bm)->deleteAdminBooking(999);
        $this->assertSame('error', $result['status']);
    }

    public function testDeleteAdminBookingSucceeds(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(['id' => 1]);
        $bm->method('deleteBooking')->willReturn(true);
        $result = $this->makeService($bm)->deleteAdminBooking(1);
        $this->assertSame('success', $result['status']);
    }

    public function testDeleteAdminBookingRollsBackOnFailure(): void
    {
        $bm = $this->makeBookingModel();
        $bm->method('getAdminBookingById')->willReturn(['id' => 1]);
        $bm->method('deleteBooking')->willReturn(false);
        $bm->method('getError')->willReturn('delete error');
        $result = $this->makeService($bm)->deleteAdminBooking(1);
        $this->assertSame('error', $result['status']);
    }

    // =========================================================================
    // normalizeAdminFilters - branches
    // =========================================================================
    public function testNormalizeFiltersIgnoresInvalidStatus(): void
    {
        $svc    = $this->makeService();
        $result = $svc->normalizeAdminFilters(['status' => 'hack', 'from_date' => '', 'to_date' => '', 'search' => '']);
        $this->assertSame('', $result['status']);
    }

    public function testNormalizeFiltersIgnoresInvalidDate(): void
    {
        $svc    = $this->makeService();
        $result = $svc->normalizeAdminFilters(['from_date' => 'not-a-date', 'to_date' => '99-99-9999']);
        $this->assertSame('', $result['from_date']);
        $this->assertSame('', $result['to_date']);
    }

    public function testNormalizeFiltersAcceptsValidDate(): void
    {
        $svc    = $this->makeService();
        $result = $svc->normalizeAdminFilters(['from_date' => '2026-01-01', 'to_date' => '2026-12-31']);
        $this->assertSame('2026-01-01', $result['from_date']);
        $this->assertSame('2026-12-31', $result['to_date']);
    }

    public function testNormalizeFiltersTrimSearch(): void
    {
        $svc    = $this->makeService();
        $result = $svc->normalizeAdminFilters(['search' => '  Nguyen  ']);
        $this->assertSame('Nguyen', $result['search']);
    }

    public function testNormalizeFiltersAllAllowedStatuses(): void
    {
        $svc = $this->makeService();
        foreach (['pending', 'paid', 'canceled'] as $s) {
            $result = $svc->normalizeAdminFilters(['status' => $s]);
            $this->assertSame($s, $result['status'], "Status '$s' should be accepted");
        }
    }
}
