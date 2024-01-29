CREATE TABLE timesheet_history (
    id INT,
    employee_id SERIAL,
    task VARCHAR(200),
    start_time TIMESTAMP WITHOUT TIME ZONE,
    end_time TIMESTAMP WITHOUT TIME ZONE,
    CONSTRAINT employee_id FOREIGN KEY (employee_id) REFERENCES employees (employee_id)
);

CREATE OR REPLACE FUNCTION delete_timesheet() RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO timesheet_history (id, employee_id, task, start_time, end_time)
  VALUES (OLD.id, OLD.employee_id, OLD.task, OLD.start_time, OLD.end_time);
  RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER delete_timesheet
BEFORE DELETE ON timesheet
FOR EACH ROW
EXECUTE FUNCTION delete_timesheet();