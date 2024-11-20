MS-Signup/
├── api/
│   ├── approve_ms_register.php
│   ├── create_kpi.php
│   ├── create_ms_regiser.php
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
  `kpi` TEXT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE s2config.kpi_history (
  `id` int NOT NULL AUTO_INCREMENT,
  `stage_id` int NOT NULL,
  `kpi_id` int NOT NULL,
  `old_kpi` TEXT,
  `is_temporary` boolean DEFAULT false,
  `is_use` boolean DEFAULT true,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE s2config.ms_signup_list
ADD COLUMN process_deal TINYINT(1) NOT NULL DEFAULT 0 AFTER confirmation;

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