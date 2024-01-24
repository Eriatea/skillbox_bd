CREATE OR REPLACE FUNCTION checking_intersections_of_employee_timesheets() RETURNS TRIGGER AS $$
BEGIN
  IF EXISTS (
    SELECT 1 FROM timesheet
    WHERE employee = NEW.employee
    AND id <> NEW.id
    AND end_time IS NULL
  ) THEN
    RAISE EXCEPTION 'You cannot add or change a timesheet while there are unfinished tasks for the same employee.';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER checking_intersections_of_employee_timesheets_before_insert
BEFORE INSERT ON timesheet
FOR EACH ROW
EXECUTE FUNCTION checking_intersections_of_employee_timesheets();

CREATE TRIGGER checking_intersections_of_employee_timesheets_before_update
BEFORE UPDATE ON timesheet
FOR EACH ROW
EXECUTE FUNCTION checking_intersections_of_employee_timesheets();