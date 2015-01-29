BEGIN;

CREATE USER iadvize WITH PASSWORD 'iadvize';

CREATE DATABASE iadvize
  WITH OWNER = postgres
       ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'fr_FR.UTF-8'
       LC_CTYPE = 'fr_FR.UTF-8'
       CONNECTION LIMIT = -1;

GRANT ALL PRIVILEGES ON DATABASE iadvize to iadvize;

CREATE TABLE vdm
(
  id_vdm serial NOT NULL,
  id integer NOT NULL,
  date_post timestamp with time zone NOT NULL,
  author text NOT NULL,
  content text NOT NULL,
  CONSTRAINT id_vdm_pkey PRIMARY KEY (id_vdm)
);

ALTER TABLE vdm
  OWNER TO iadvize;

COMMIT;
