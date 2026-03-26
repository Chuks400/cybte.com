-- Create payments table for CNY-based payment system
-- Supports Alipay, WeChat Pay, and future Stripe/Crypto

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'CNY',
    method VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    qr_code TEXT,
    transaction_id VARCHAR(100),
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create payment plans table for storing plan details
CREATE TABLE IF NOT EXISTS payment_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_key VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    name_zh VARCHAR(100),
    price_cny DECIMAL(10,2) NOT NULL,
    price_usd DECIMAL(10,2),
    duration_days INT DEFAULT 30,
    traffic_gb INT,
    device_limit INT,
    features JSON,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default CNY pricing plans based on user's reference images
INSERT INTO payment_plans (plan_key, name, name_zh, price_cny, traffic_gb, device_limit, features, sort_order) VALUES
('basic', 'Basic · Single Device', '基础版 · 单设备', 9.90, 80, 1, '["single_device", "student_price", "netflix_gpt", "hk_jp_us_kr_sg_au_in"]', 1),
('regular', 'Regular · Phone + Computer', '常规版 · 手机+电脑', 16.90, 180, 2, '["phone_pc", "recommend", "netflix_gpt", "hk_jp_us_kr_sg_au_in"]', 2),
('standard', 'Standard · High Bandwidth', '标准版 · 高带宽', 24.90, 499, 3, '["high_bandwidth", "phone_pc", "netflix_gpt", "hk_jp_us_kr_sg_au_in"]', 3),
('premium', 'Premium · Advance Node', '高级版 · 高级节点', 49.90, 1024, 5, '["premium_node", "ultra_speed", "netflix_gpt", "hk_jp_us_kr_sg_au_in"]', 4);

-- Add webhook_logs table for payment provider callbacks
CREATE TABLE IF NOT EXISTS payment_webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(20) NOT NULL,
    event_type VARCHAR(50),
    payload TEXT,
    order_id VARCHAR(50),
    processed TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
