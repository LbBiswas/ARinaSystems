-- Update documents table to support new document management features
-- Add additional columns if they don't exist

-- Add category column if it doesn't exist
ALTER TABLE documents ADD category VARCHAR(50) DEFAULT 'general';

-- Add description column if it doesn't exist  
ALTER TABLE documents ADD description TEXT;

-- Add updated_at column if it doesn't exist
ALTER TABLE documents ADD updated_at TIMESTAMP NULL DEFAULT NULL;

-- Update existing documents to have general category
UPDATE documents SET category = 'general' WHERE category IS NULL OR category = '';

-- Add indexes for better performance
CREATE INDEX idx_documents_category ON documents(category);
CREATE INDEX idx_documents_uploaded_by ON documents(uploaded_by);
CREATE INDEX idx_documents_upload_date ON documents(upload_date);
CREATE INDEX idx_documents_file_type ON documents(file_type);

-- Sample categories data
-- You can run these to populate some sample categories
-- UPDATE documents SET category = 'invoice' WHERE original_name LIKE '%invoice%';
-- UPDATE documents SET category = 'receipt' WHERE original_name LIKE '%receipt%';
-- UPDATE documents SET category = 'contract' WHERE original_name LIKE '%contract%';
-- UPDATE documents SET category = 'report' WHERE original_name LIKE '%report%';