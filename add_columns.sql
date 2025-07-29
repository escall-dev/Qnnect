-- Add instructor_id and subject_id columns to tbl_attendance table
ALTER TABLE tbl_attendance 
ADD COLUMN instructor_id INT DEFAULT NULL,
ADD COLUMN subject_id INT DEFAULT NULL;

-- Add foreign key constraints
ALTER TABLE tbl_attendance
ADD CONSTRAINT fk_instructor_id FOREIGN KEY (instructor_id) REFERENCES tbl_instructors(instructor_id) ON DELETE SET NULL,
ADD CONSTRAINT fk_subject_id FOREIGN KEY (subject_id) REFERENCES tbl_subjects(subject_id) ON DELETE SET NULL; 