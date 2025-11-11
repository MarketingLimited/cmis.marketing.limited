DO $$
DECLARE
    v_rec RECORD;
BEGIN
    FOR v_rec IN
        SELECT view_name FROM view_definitions_backup
        WHERE depends_on_refactored = true
        ORDER BY 
            CASE view_name 
                WHEN 'campaigns' THEN 1
                WHEN 'integrations' THEN 2
                ELSE 3
            END
    LOOP
        EXECUTE format('DROP VIEW IF EXISTS cmis.%I CASCADE', v_rec.view_name);
        RAISE NOTICE 'Dropped view: cmis.%', v_rec.view_name;
    END LOOP;
END $$;
