-- PaperVista Database Setup Script
-- Run this script to create the required database and tables

-- Create database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS papervista;
USE papervista;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    institution VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Papers table
CREATE TABLE IF NOT EXISTS papers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500),
    authors TEXT,
    abstract TEXT,
    publication_year VARCHAR(10),
    journal_name VARCHAR(255),
    doi VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    text_content LONGTEXT,
    processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Summaries table
CREATE TABLE IF NOT EXISTS summaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paper_id INT,
    user_id INT,
    summary_type ENUM('short', 'medium', 'detailed') NOT NULL,
    content LONGTEXT NOT NULL,
    word_count INT,
    processing_time DECIMAL(5,2),
    ai_model_used VARCHAR(50),
    user_rating INT CHECK (user_rating >= 1 AND user_rating <= 5),
    user_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paper_id) REFERENCES papers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ADDED FOR VIEW TRACKING
CREATE TABLE IF NOT EXISTS paper_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paper_id INT NOT NULL,
    user_id INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_view (paper_id, user_id),
    FOREIGN KEY (paper_id) REFERENCES papers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Search logs table (for analytics)
CREATE TABLE IF NOT EXISTS search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    search_query TEXT NOT NULL,
    result_count INT DEFAULT 0,
    search_type ENUM('text', 'filter', 'advanced') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- User sessions table (optional, for better session management)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_papers_uploaded_by ON papers(uploaded_by);
CREATE INDEX idx_papers_created_at ON papers(created_at);
CREATE INDEX idx_summaries_paper_id ON summaries(paper_id);
CREATE INDEX idx_summaries_user_id ON summaries(user_id);
CREATE INDEX idx_summaries_created_at ON summaries(created_at);
CREATE INDEX idx_search_logs_user_id ON search_logs(user_id);
CREATE INDEX idx_search_logs_created_at ON search_logs(created_at);

-- Insert default admin user (password: admin123)
-- Note: In production, use a more secure password
INSERT IGNORE INTO users (email, password, first_name, last_name, role) VALUES
('admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin');

-- Insert sample users for testing (optional)
INSERT IGNORE INTO users (email, password, first_name, last_name, institution) VALUES
('researcher@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Researcher', 'University of Technology'),
('student@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Student', 'University of Technology');

-- Sample data for demonstration (optional)
INSERT IGNORE INTO papers (title, authors, abstract, publication_year, file_path, file_size, file_type, text_content, uploaded_by, processing_status) VALUES
('Sample Research Paper: AI in Academic Research', 'Dr. Jane Smith, Prof. John Doe', 'This paper explores the integration of artificial intelligence in academic research methodologies and its impact on research efficiency and accuracy. The study examines various AI applications in data analysis, literature review, and hypothesis generation.', '2024', 'sample_paper.pdf', 1024000, 'application/pdf', 'This is sample text content extracted from the PDF file for demonstration purposes. In a real implementation, this would contain the full text content of the research paper.', 1, 'completed');

-- Create a view for user statistics (optional)
CREATE OR REPLACE VIEW user_stats AS
SELECT
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    u.institution,
    COUNT(DISTINCT p.id) as papers_uploaded,
    COUNT(DISTINCT s.id) as summaries_generated,
    MAX(u.last_login_at) as last_login
FROM users u
LEFT JOIN papers p ON u.id = p.uploaded_by
LEFT JOIN summaries s ON u.id = s.user_id
GROUP BY u.id, u.first_name, u.last_name, u.email, u.institution;

-- Create a view for paper statistics (optional)
CREATE OR REPLACE VIEW paper_stats AS
SELECT
    p.id,
    p.title,
    p.authors,
    p.created_at as upload_date,
    u.first_name,
    u.last_name,
    COUNT(s.id) as summary_count,
    AVG(s.user_rating) as avg_rating
FROM papers p
LEFT JOIN users u ON p.uploaded_by = u.id
LEFT JOIN summaries s ON p.id = s.paper_id
GROUP BY p.id, p.title, p.authors, p.created_at, u.first_name, u.last_name;

-- Set up some basic constraints and validations
ALTER TABLE users ADD CONSTRAINT chk_email_format CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$');
ALTER TABLE papers ADD CONSTRAINT chk_file_size CHECK (file_size > 0 AND file_size <= 52428800); -- Max 50MB

-- Create cleanup procedure for old sessions (optional)
DELIMITER //
CREATE PROCEDURE cleanup_old_sessions()
BEGIN
    DELETE FROM user_sessions WHERE expires_at < NOW();
END //
DELIMITER ;

-- Create event to run cleanup daily (optional)
CREATE EVENT IF NOT EXISTS daily_session_cleanup
ON SCHEDULE EVERY 1 DAY
DO CALL cleanup_old_sessions();

-- Show created tables and structure
SHOW TABLES;

-- Show table structures
DESCRIBE users;
DESCRIBE papers;
DESCRIBE summaries;
DESCRIBE search_logs;

-- Display success message
SELECT 'PaperVista database setup completed successfully!' as status;