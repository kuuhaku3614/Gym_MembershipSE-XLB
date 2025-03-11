-- Add is_paid column to memberships table if it doesn't exist
ALTER TABLE memberships
ADD COLUMN IF NOT EXISTS is_paid TINYINT(1) NOT NULL DEFAULT 0;

-- Add is_paid column to rental_subscriptions table if it doesn't exist
ALTER TABLE rental_subscriptions
ADD COLUMN IF NOT EXISTS is_paid TINYINT(1) NOT NULL DEFAULT 0;

-- Add amount column to memberships table if it doesn't exist
ALTER TABLE memberships
ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- Add amount column to rental_subscriptions table if it doesn't exist
ALTER TABLE rental_subscriptions
ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2) NOT NULL DEFAULT 0.00;
