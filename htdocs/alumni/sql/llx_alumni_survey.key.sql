-- Copyright (C) 2023 Alice Adminson 
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_alumni_survey ADD INDEX idx_alumni_survey_rowid (rowid);
ALTER TABLE llx_alumni_survey ADD INDEX idx_alumni_survey_entity (entity);
ALTER TABLE llx_alumni_survey ADD INDEX idx_alumni_survey_lastname (lastname);
ALTER TABLE llx_alumni_survey ADD INDEX idx_alumni_survey_email (email);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_alumni_survey ADD UNIQUE INDEX uk_alumni_survey_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_alumni_survey ADD CONSTRAINT llx_alumni_survey_fk_field FOREIGN KEY (fk_field) REFERENCES llx_alumni_myotherobject(rowid);

ALTER TABLE llx_alumni_survey ADD UNIQUE INDEX uk_alumni_survey_lastname_firstname(lastname, firstname);