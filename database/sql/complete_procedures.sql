CREATE PROCEDURE cmis.cleanup_scheduler()
    LANGUAGE sql
    AS $$
  SELECT cmis.auto_delete_unapproved_assets();
$$;


ALTER PROCEDURE cmis.cleanup_scheduler() OWNER TO begin;

--
-- Name: cmis_immutable_setweight(tsvector, "char"); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.cmis_immutable_setweight(vec tsvector, w "char") RETURNS tsvector
    LANGUAGE plpgsql IMMUTABLE
    AS $$
BEGIN
  RETURN setweight(vec, w);
END;
$$;


ALTER FUNCTION cmis.cmis_immutable_setweight(vec tsvector, w "char") OWNER TO begin;

--
-- Name: cmis_immutable_tsvector(regconfig, text); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.cmis_immutable_tsvector(cfg regconfig, txt text) RETURNS tsvector
    LANGUAGE plpgsql IMMUTABLE
    AS $$
