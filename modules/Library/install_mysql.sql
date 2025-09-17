/**
 * Install MySQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Library module
 */

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
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Library.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Library/Library.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Library.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Library/Library.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Library.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Library/Library.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Library.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Library/Loans.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Loans.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Library/Loans.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Loans.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Library/Loans.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Loans.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Library/Loans.php', 'Y', null
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/Loans.php'
    AND profile_id=0);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Library/DocumentFields.php', 'Y', 'Y'
FROM DUAL
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Library/DocumentFields.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Library/LoansBreakdown.php', 'Y', 'Y'
FROM DUAL
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

CREATE TABLE IF NOT EXISTS library_documents (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- school_id integer NOT NULL,
    ref varchar(50) NOT NULL,
    category_id integer NOT NULL,
    title text NOT NULL,
    description longtext,
    author text,
    year numeric(4,0),
    created_at timestamp DEFAULT current_timestamp,
    created_by integer NOT NULL
);

--
-- Name: library_documents_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace:
--

DELIMITER $$
CREATE PROCEDURE create_library_documents_ind()
BEGIN
    DECLARE index_exists integer DEFAULT 0;

    SELECT count(1) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE table_schema=DATABASE()
    AND table_name='library_documents'
    AND index_name='library_documents_ind';

    IF index_exists=0 THEN
        CREATE INDEX library_documents_ind ON library_documents (ref);
    END IF;
END $$
DELIMITER ;

CALL create_library_documents_ind();
DROP PROCEDURE create_library_documents_ind;


/**
 * Library Loans table
 */
--
-- Name: library_loans; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--
-- Note: specify NULL for date_return timestamp column, or else MySQL will default to current_timestamp!
--

CREATE TABLE IF NOT EXISTS library_loans (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    document_id integer NOT NULL,
    user_id integer NOT NULL,
    comments text,
    date_begin date NOT NULL,
    date_due date NOT NULL,
    date_return timestamp NULL,
    created_at timestamp DEFAULT current_timestamp,
    created_by integer NOT NULL
);


/**
 * Categories table
 */
--
-- Name: categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE TABLE IF NOT EXISTS library_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    -- school_id integer NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    color varchar(255),
    published_profiles text,
    published_grade_levels text
);
