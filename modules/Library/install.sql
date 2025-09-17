/**
 * Install PostgreSQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Library module
 */

-- Fix #102 error language "plpgsql" does not exist
-- http://timmurphy.org/2011/08/27/create-language-if-it-doesnt-exist-in-postgresql/
--
-- Name: create_language_plpgsql(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION create_language_plpgsql()
RETURNS BOOLEAN AS $$
    CREATE LANGUAGE plpgsql;
    SELECT TRUE;
$$ LANGUAGE SQL;

SELECT CASE WHEN NOT (
    SELECT TRUE AS exists FROM pg_language
    WHERE lanname='plpgsql'
    UNION
    SELECT FALSE AS exists
    ORDER BY exists DESC
    LIMIT 1
) THEN
    create_language_plpgsql()
ELSE
    FALSE
END AS plpgsql_created;

DROP FUNCTION create_language_plpgsql();


/**
 * profile_exceptions Table
 *
 * profile_id:
 * - 0: student
 * - 1: admin
 * - 2: teacher
 * - 3: parent
 * modname: should match the Menu.php entries
 * can_use: 'Y'
 * can_edit: 'Y' or null (generally null for non admins)
 */
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Library/Library.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Library.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Library/Library.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Library.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Library/Library.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Library.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Library/Library.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Library.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Library/Loans.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Loans.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Library/Loans.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Loans.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Library/Loans.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Loans.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Library/Loans.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Loans.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Library/DocumentFields.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/DocumentFields.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Library/LoansBreakdown.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/LoansBreakdown.php'
    AND profile_id=1);


/**
 * Add module tables
 */

/**
 * Library Document table
 */
--
-- Name: library_documents; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_library_documents() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'library_documents') THEN
    RAISE NOTICE 'Table "library_documents" already exists.';
    ELSE
        CREATE TABLE library_documents (
            id serial PRIMARY KEY,
            -- school_id integer NOT NULL,
            ref varchar(50) NOT NULL,
            category_id integer NOT NULL,
            title text NOT NULL,
            description text,
            author text,
            year numeric(4,0),
            created_at timestamp DEFAULT current_timestamp,
            created_by integer NOT NULL
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_library_documents();
DROP FUNCTION create_table_library_documents();


--
-- Name: library_documents_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_index_library_documents_ind() RETURNS void AS
$func$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_class c
        JOIN pg_namespace n ON n.oid=c.relnamespace
        WHERE c.relname='library_documents_ind'
        AND n.nspname=CURRENT_SCHEMA()
    ) THEN
        CREATE INDEX library_documents_ind ON library_documents (ref);
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_index_library_documents_ind();
DROP FUNCTION create_index_library_documents_ind();


/**
 * Library Loans table
 */
--
-- Name: library_loans; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_library_loans() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'library_loans') THEN
    RAISE NOTICE 'Table "library_loans" already exists.';
    ELSE
        CREATE TABLE library_loans (
            id serial PRIMARY KEY,
            document_id integer NOT NULL,
            user_id integer NOT NULL,
            comments text,
            date_begin date NOT NULL,
            date_due date NOT NULL,
            date_return timestamp NULL,
            created_at timestamp DEFAULT current_timestamp,
            created_by integer NOT NULL
       );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_library_loans();
DROP FUNCTION create_table_library_loans();


/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_library_categories() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'library_categories') THEN
    RAISE NOTICE 'Table "library_categories" already exists.';
    ELSE
        CREATE TABLE library_categories (
            id serial PRIMARY KEY,
            -- school_id integer NOT NULL,
            title text NOT NULL,
            sort_order numeric,
            color varchar(255),
            published_profiles text,
            published_grade_levels text
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_library_categories();
DROP FUNCTION create_table_library_categories();
