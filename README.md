# Salon Booking System - Phase 1

A lean, production-ready booking system for a single-location hair salon built with Laravel 11 and PostgreSQL.

## Features (Phase 1)

- **Services Catalog**: CRUD for salon services with pricing
- **Availability Engine**: Dynamic slot generation with overlap prevention
- **Booking Flow**: Hold → Confirm lifecycle with payment integration ready
- **Concurrency Safe**: PostgreSQL advisory locks + exclusion constraints
- **Policy Settings**: Configurable business rules (deposit %, late fees, etc.)

## Requirements

- PHP 8.3+
- PostgreSQL 14+ (with btree_gist extension)
- Composer
- Laravel 11.x

## Installation

1. **Install dependencies**:
   ```bash
   composer install
   ```

2. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure database** (PostgreSQL):
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=salon_booking
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Run migrations**:
   ```bash
   php artisan migrate
   ```

5. **Seed sample services**:
   ```bash
   php artisan db:seed --class=ServiceSeeder
   ```

6. **Start the scheduler** (for expiring holds):
   ```bash
   php artisan schedule:work
   ```

## API Endpoints

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/services` | List all active services |
| GET | `/api/availability?service_id=1&date=2024-01-15` | Get available slots for a date |
| GET | `/api/availability/dates?service_id=1&days=30` | Get dates with availability |
| POST | `/api/bookings/hold` | Hold an appointment slot |
| GET | `/api/bookings/{id}` | Get appointment details |
| POST | `/api/bookings/{id}/cancel` | Cancel an appointment |

### Hold Appointment Request

```json
{
  "service_id": 1,
  "starts_at": "2024-01-15T10:00:00Z",
  "client": {
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com",
    "phone": "555-1234",
    "date_of_birth": "1990-05-15"
  },
  "photo_consent": "full_ok",
  "policy_acknowledged": true
}
```

## Policy Settings

Default values configured in the database:

| Setting | Default | Description |
|---------|---------|-------------|
| `deposit_percentage` | 40 | Deposit percentage of service price |
| `no_show_charge_percentage` | 70 | No-show charge percentage |
| `late_cancel_charge_percentage` | 70 | Late cancellation charge percentage |
| `reschedule_cutoff_hours` | 48 | Hours before appointment for free reschedule |
| `late_fee_threshold_minutes` | 20 | Minutes late before fee applies |
| `late_fee_cents` | 2000 | Late fee amount ($20) |
| `auto_cancel_minutes_late` | 40 | Minutes late before auto-cancel |
| `squeeze_in_fee_cents` | 4000 | Emergency/squeeze-in fee ($40) |
| `minimum_client_age` | 15 | Minimum client age |
| `hold_duration_minutes` | 10 | How long a hold lasts |
| `business_hours_start` | 09:00 | Business hours start |
| `business_hours_end` | 18:00 | Business hours end |

## Appointment Statuses

- `held` - Slot is temporarily reserved (expires after 10 min)
- `confirmed` - Payment received, booking confirmed
- `cancelled_by_client` - Client cancelled
- `cancelled_by_salon` - Salon cancelled
- `cancelled_by_system` - Hold expired
- `no_show` - Client didn't show up
- `completed` - Appointment finished

## Concurrency Protection

This system uses multiple layers of protection against double-booking:

1. **PostgreSQL Advisory Locks**: Transaction-level locks keyed on timestamp
2. **Exclusion Constraint**: Database-level prevention of overlapping time ranges
3. **Application-level overlap check**: Additional verification inside transaction

## Project Structure

```
app/
├── Console/
│   ├── Commands/
│   │   └── ExpireHoldsCommand.php
│   └── Kernel.php
├── Enums/
│   ├── AppointmentStatus.php
│   ├── PaymentStatus.php
│   ├── PaymentType.php
│   └── PhotoConsent.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AvailabilityController.php
│   │       ├── BookingController.php
│   │       └── ServiceController.php
│   └── Requests/
│       ├── CancelAppointmentRequest.php
│       └── HoldAppointmentRequest.php
├── Jobs/
│   └── ExpireHeldAppointments.php
├── Models/
│   ├── Appointment.php
│   ├── Client.php
│   ├── Payment.php
│   ├── PolicySetting.php
│   └── Service.php
└── Services/
    ├── AvailabilityService.php
    └── BookingService.php
database/
├── migrations/
│   ├── 2024_01_01_000001_create_services_table.php
│   ├── 2024_01_01_000002_create_clients_table.php
│   ├── 2024_01_01_000003_create_appointments_table.php
│   ├── 2024_01_01_000004_create_payments_table.php
│   └── 2024_01_01_000005_create_policy_settings_table.php
└── seeders/
    └── ServiceSeeder.php
routes/
└── api.php
```

## Next Steps (Phase 2)

- [ ] Stripe payment integration with webhooks
- [ ] Admin authentication & dashboard
- [ ] Email notifications (confirmation, cancellation)
- [ ] Calendar UI (day/week views)

## License

Proprietary - All rights reserved.
