<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php";
require $_SERVER["DOCUMENT_ROOT"] .
    "/bitrix/modules/main/include/prolog_before.php";
require $_SERVER["DOCUMENT_ROOT"] . "/page-custom/ms-signup/env.php";

$APPLICATION->SetTitle("Đăng ký MS");

if (!$USER->IsAuthorized()) {
    LocalRedirect('/auth/');
}

$userID = $USER->GetID();
$user = CUser::GetByID($userID)->Fetch();
if ($user) {
    $userFullName = htmlspecialchars($user["LAST_NAME"]) . " " . htmlspecialchars($user["NAME"]);
    $userEmail = $user["EMAIL"];

    $employeeId = $user[$config['user_employee_id_field']];
    $enumList = CUserFieldEnum::GetList(array(), array('USER_FIELD_ID' => 966));
    $typeMS = array();
    while ($enum = $enumList->Fetch()) {
        $typeMS[$enum['ID']] = $enum['VALUE'];
    }
    $rsDepartments = CIBlockSection::GetList(array(), array("ID" => $user["UF_DEPARTMENT"]));

    $departmentLabels = [];

    while ($arDepartment = $rsDepartments->Fetch()) {
        $departmentLabels[] = $arDepartment["NAME"];
    }

    $departmentId = $user["UF_DEPARTMENT"];
    $msId = $user[$config['user_type_ms_field']];
} else {
    LocalRedirect('/');
}
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "../../templates/head.php"; ?>
    <?php include "assets/style.php"; ?>
</head>

<body>
    <div id="form-register">
        <?php include "templates/form_register.php"; ?>
    </div>

    <?php include "../../templates/script.php"; ?>
    <?php include "templates/vue_register_script.php"; ?>
</body>

</html>

<?php require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"; ?>