
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the product',
  `name` VARCHAR(255) NOT NULL COMMENT 'Primary product name',
  `description` TEXT NULL COMMENT 'Detailed product description',
  `category` VARCHAR(100) NULL COMMENT 'Product category (e.g., Electronics, Furniture)',
  `brand` VARCHAR(100) NULL COMMENT 'Product brand',
  `model_number` VARCHAR(100) NULL COMMENT 'Product model number (if applicable)',
  `specifications` JSON NULL COMMENT 'Store product specifications as a JSON object for flexibility',
  `base_image_url` VARCHAR(2048) NULL COMMENT 'URL to a representative product image',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the product record was created',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp when the product record was last updated',
  PRIMARY KEY (`product_id`),
  INDEX `idx_product_name` (`name`),
  INDEX `idx_product_category` (`category`),
  INDEX `idx_product_brand` (`brand`),
  FULLTEXT KEY `ft_name_desc` (`name`, `description`) COMMENT 'Full-text index for searching name and description'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores core product details';

--
-- Table structure for table `prices`
-- Stores price information for products from different sources.
-- This table captures data that changes frequently (prices, availability).
--
CREATE TABLE IF NOT EXISTS `prices` (
  `price_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for this price entry',
  `product_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key linking to the products table',
  `source` VARCHAR(50) NOT NULL COMMENT 'Source marketplace (e.g., GeM, Amazon, Flipkart)',
  `price` DECIMAL(12, 2) NULL COMMENT 'Price of the product on this source (NULL if unavailable)',
  `currency` VARCHAR(3) NOT NULL DEFAULT 'INR' COMMENT 'Currency code (e.g., INR)',
  `product_url` VARCHAR(2048) NULL COMMENT 'Direct URL to the product page on the source marketplace',
  `seller_name` VARCHAR(255) NULL COMMENT 'Seller name on the marketplace',
  `rating` DECIMAL(3, 2) NULL COMMENT 'Product rating on the source (e.g., 4.5)',
  `rating_count` INT UNSIGNED NULL COMMENT 'Number of ratings/reviews',
  `is_available` BOOLEAN NULL COMMENT 'Flag indicating if the product is currently available/in stock (NULL if unknown)',
  `fetched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when this price data was fetched/updated',
  PRIMARY KEY (`price_id`),
  INDEX `idx_price_product_id` (`product_id`),
  INDEX `idx_price_source` (`source`),
  INDEX `idx_fetched_at` (`fetched_at`),
  -- Unique constraint to help manage data freshness - maybe one entry per product/source per day? Depends on update strategy.
  -- Consider constraints based on how you plan to update data (e.g., UNIQUE(product_id, source)).
  CONSTRAINT `fk_price_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `products` (`product_id`)
    ON DELETE CASCADE -- If a product is deleted, delete its associated prices
    ON UPDATE CASCADE -- If a product_id changes (less likely), update here too
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores product prices from various sources and times';



 
 CREATE TABLE IF NOT EXISTS `watchlist_items` (
  `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the watchlist entry',
  `user_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key linking to the users table',
  `product_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key linking to the products table',
  `source` VARCHAR(50) NOT NULL COMMENT 'Which source listing is being watched (e.g., GeM, Amazon)',
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the item was added',
  PRIMARY KEY (`item_id`),
  INDEX `idx_watchlist_user_id` (`user_id`),
  INDEX `idx_watchlist_product_id` (`product_id`),
  UNIQUE KEY `uq_user_product_source` (`user_id`, `product_id`, `source`),
  CONSTRAINT `fk_watchlist_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_watchlist_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `products` (`product_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Items saved by users to their watchlist';

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the user',
  `username` VARCHAR(50) NOT NULL COMMENT 'User chosen username',
  `email` VARCHAR(255) NOT NULL COMMENT 'User email address, used for login/recovery',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed user password (use password_hash() in PHP)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the user registered',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp when user info was last updated',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User account information';

-- SQL for `password_resets` table using DATETIME
CREATE TABLE IF NOT EXISTS `password_resets` (
  `reset_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the reset request',
  `user_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key linking to the users table',
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed version of the reset token (store securely)',
  `selector` VARCHAR(32) NULL COMMENT 'Optional: Selector for lookup if using selector/verifier pattern',
  `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the reset was requested',
  `expires_at` DATETIME NOT NULL COMMENT 'Timestamp when the token expires', -- Changed to DATETIME
  PRIMARY KEY (`reset_id`),
  INDEX `idx_reset_user_id` (`user_id`),
  INDEX `idx_reset_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_reset_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`user_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores password reset tokens and expiry';