-- +--------------------------------------------------------------------+
-- | CiviCRM version 5                                                  |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2018                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
--
-- Generated from schema.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--


-- +--------------------------------------------------------------------+
-- | CiviCRM version 5                                                  |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2018                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
--
-- Generated from drop.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--
-- /*******************************************************
-- *
-- * Clean up the exisiting tables
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_election_vote`;
DROP TABLE IF EXISTS `civicrm_election_result`;
DROP TABLE IF EXISTS `civicrm_election_position`;
DROP TABLE IF EXISTS `civicrm_election_nomination_seconder`;
DROP TABLE IF EXISTS `civicrm_election_nomination`;
DROP TABLE IF EXISTS `civicrm_election`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * civicrm_election
-- *
-- * FIXME
-- *
-- *******************************************************/
CREATE TABLE `civicrm_election` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique Election ID',
     `name` varchar(255)    COMMENT 'Name of the election.',
     `description` text    ,
     `visibility_start_date` datetime    COMMENT 'Election visibility start date',
     `visibility_end_date` datetime    COMMENT 'Election visibility end date',
     `nomination_start_date` datetime    COMMENT 'Election nomination start date',
     `nomination_end_date` datetime    COMMENT 'Election nomination end date',
     `advertise_candidates_date` datetime    COMMENT 'Date from when candidates are available for viewing.',
     `voting_start_date` datetime    COMMENT 'Election voting start date',
     `voting_end_date` datetime    COMMENT 'Election voting end date',
     `result_date` datetime    COMMENT 'Election result date, after this date election results are published.',
     `result_status` int unsigned   DEFAULT 0 COMMENT 'Election result status, for admins to manage election result visibility manually.',
     `is_deleted` tinyint   DEFAULT 0 COMMENT 'Boolean field to soft delete an election.',
     `is_visible` tinyint   DEFAULT 0 COMMENT 'Boolean field to set if election is visible or not.',
     `has_results_generated` tinyint   DEFAULT 0 COMMENT 'Boolean field to set if results are generated.',
     `anonymize_votes` tinyint   DEFAULT 1 COMMENT 'Boolean field to anonymize votes when results are generated.',
     `allow_revote` tinyint   DEFAULT 0 COMMENT 'Boolean field to set if members are allowed to re-vote in election.',
     `required_nominations` int unsigned DEFAULT 2 COMMENT 'Number of required nominations to become eligible candidate.',
     `allowed_groups` text  COMMENT 'List of groups, members of it are allowed to participate in election.',
     `created_at` timestamp   DEFAULT CURRENT_TIMESTAMP COMMENT 'Date on which election created.',
     `updated_at` timestamp  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date on which election was updated.',
     `created_by` int unsigned    COMMENT 'FK to Contact who created particular election' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_election_created_by FOREIGN KEY (`created_by`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE  
)    ;

-- /*******************************************************
-- *
-- * civicrm_election_position
-- *
-- * FIXME
-- *
-- *******************************************************/
CREATE TABLE `civicrm_election_position` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique ElectionPosition ID',
     `name` varchar(255)    COMMENT 'Name of the position.',
     `quantity` int unsigned    COMMENT 'Quantity of a this position.',
     `sortorder` int unsigned    COMMENT 'Order of this position.',
     `description` text    ,
     `created_at` timestamp   DEFAULT CURRENT_TIMESTAMP COMMENT 'Date on which election position created.',
     `updated_at` timestamp  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date on which election position was updated.',
     `election_id` int unsigned    COMMENT 'FK to Election',
     `created_by` int unsigned    COMMENT 'FK to Contact who created particular position',
     `result_status` varchar(255)    COMMENT 'Result status for this position.'
,
        PRIMARY KEY (`id`)


,          CONSTRAINT FK_civicrm_election_position_election_id FOREIGN KEY (`election_id`) REFERENCES `civicrm_election`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_election_position_created_by FOREIGN KEY (`created_by`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)    ;

-- /*******************************************************
-- *
-- * civicrm_election_nomination
-- *
-- * FIXME
-- *
-- *******************************************************/
CREATE TABLE `civicrm_election_nomination` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique ElectionNomination ID',
     `comments` text    ,
     `rejection_comments` text    ,
     `is_eligible_candidate` tinyint   DEFAULT 0 COMMENT 'Boolean field to identity if nomination is a valid candidate.',
     `has_accepted_nomination` tinyint   DEFAULT 0 COMMENT 'Boolean field to identity if nomination has been accepted by candidate.',
     `has_rejected_nomination` tinyint   DEFAULT 0 COMMENT 'Boolean field to identity if nomination has been rejected by candidate.',
     `created_at` timestamp   DEFAULT CURRENT_TIMESTAMP COMMENT 'Date on which election nomination created.',
     `updated_at` timestamp  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date on which election nomination was updated.',
     `member_nominee` int unsigned    COMMENT 'FK to Contact for which this nomination is added.',
     `election_position_id` int unsigned    COMMENT 'FK to ElectionPosition for which this nomination is added.' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_election_nomination_member_nominee FOREIGN KEY (`member_nominee`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_election_nomination_election_position_id FOREIGN KEY (`election_position_id`) REFERENCES `civicrm_election_position`(`id`) ON DELETE CASCADE  
)    ;

-- /*******************************************************
-- *
-- * civicrm_election_nomination_seconder
-- *
-- * FIXME
-- *
-- *******************************************************/
CREATE TABLE `civicrm_election_nomination_seconder` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique ElectionNominationSeconder ID',
     `description` text    ,
     `member_nominator` int unsigned    COMMENT 'FK to Contact who nominated member_nominee for particular position.',
     `election_nomination_id` int unsigned    COMMENT 'FK to ElectionNomination for which this seconder is added.' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_election_nomination_seconder_member_nominator FOREIGN KEY (`member_nominator`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_election_nomination_seconder_election_nomination_id FOREIGN KEY (`election_nomination_id`) REFERENCES `civicrm_election_nomination`(`id`) ON DELETE CASCADE  
)    ;

-- /*******************************************************
-- *
-- * civicrm_election_result
-- *
-- * FIXME
-- *
-- *******************************************************/
CREATE TABLE `civicrm_election_result` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique ElectionResult ID',
     `rank` int unsigned    COMMENT 'Rank of a member for particular position.',
     `election_position_id` int unsigned    COMMENT 'FK to ElectionPosition for which this result is added.',
     `election_nomination_id` int unsigned    COMMENT 'FK to ElectionNomination for which this rank is added.' ,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_election_result_election_position_id FOREIGN KEY (`election_position_id`) REFERENCES `civicrm_election_position`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_election_result_election_nomination_id FOREIGN KEY (`election_nomination_id`) REFERENCES `civicrm_election_nomination`(`id`) ON DELETE CASCADE  
)    ;

-- /*******************************************************
-- *
-- * civicrm_election_vote
-- *
-- * FIXME
-- *
-- *******************************************************/
CREATE TABLE `civicrm_election_vote` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique ElectionVote ID',
     `rank` int unsigned    COMMENT 'Rank of a nomination for particular position.',
     `election_nomination_id` int unsigned    COMMENT 'FK to ElectionNomination for which this vote is counted.',
     `member_id` int unsigned    COMMENT 'FK to Contact who added this vote.' 
,
      `created_at` timestamp   DEFAULT CURRENT_TIMESTAMP COMMENT 'Date on which vote has been added.',
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_election_vote_election_nomination_id FOREIGN KEY (`election_nomination_id`) REFERENCES `civicrm_election_nomination`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_election_vote_member_id FOREIGN KEY (`member_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE  
)    ;

 
