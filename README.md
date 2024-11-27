MS-Signup/
├── api/
│   ├── approve_ms_register.php
│   ├── create_kpi.php
│   ├── create_ms_register.php
│   ├── final_confirm.php
│   ├── get_form_submited.php
│   ├── get_head_department.php
│   ├── get_list_proposer.php
│   ├── get_list_team_ms.php
│   ├── get_ms_signup_list.php
│   ├── get_user_kpi.php
│   ├── reject_ms_register.php
│   └── search_manager.php
├── form/
│   ├── list/
│   │   ├── assets/
│   │   │   └── style.css
│   │   ├── templates/
|   |   |   ├── ms_signup_list.php
│   │   │   └── vue_list_ms_processes_script.php
│   │   └── index.php
│   ├── register/
│   │   ├── assets/
│   │   │   └── style.css
│   │   ├── templates/
|   |   |   ├── form_register.php
│   │   │   └── vue_register_script.php
│   │   └── index.php
|   ├── unregister/
│   │   ├── assets/
│   │   │   └── style.css
│   │   ├── templates/
|   |   |   ├── form_unregister.php
│   │   │   └── vue_unregister_script.php
│   │   └── index.php
├── model/
│   ├── kpi.php
│   ├── ms_signup_list.php
│   ├── reviewer_stage.php
│   └── stage.php
├── services/
│   ├── mail_service.php
│   └── api_service.php
├── env.php
└── index.php

CREATE TABLE s2config.kpi (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `stage_id` int NOT NULL,
  `ms_list_id` int NOT NULL,
  `year` int NOT NULL,
  `kpi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE s2config.kpi_history (
  `id` int NOT NULL AUTO_INCREMENT,
  `stage_id` int NOT NULL,
  `kpi_id` int NOT NULL,
  `old_kpi` TEXT,
  `modified_by` int NOT NULL,
  `is_temporary` boolean DEFAULT false,
  `is_use` boolean DEFAULT true,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE s2config.ms_signup_list (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stage_id` int NOT NULL DEFAULT '1',
  `max_stage` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `team_ms_id` int NOT NULL,
  `type_ms_id` int NOT NULL,
  `list_propose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `confirmation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `process_deal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed` boolean DEFAULT false,
  `flag_edit_3` boolean DEFAULT false,
  `flag_edit_4` boolean DEFAULT false,
  `comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `join_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=252 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE s2config.stage (
  `id` int NOT NULL AUTO_INCREMENT,
  `stage_id` int NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `require_kpi` boolean DEFAULT false,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE s2config.reviewer_stage (
  `id` int NOT NULL AUTO_INCREMENT,
  `reviewer_id` int NOT NULL,
  `stage_id` int NOT NULL,
  `ms_list_id` int NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci