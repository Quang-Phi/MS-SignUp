<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php";
require $_SERVER["DOCUMENT_ROOT"] .
    "/bitrix/modules/main/include/prolog_before.php";
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/env.php';

$APPLICATION->SetTitle("MS Sign Up");
?>

<!DOCTYPE html>
<html>

<head>
    <?php include "templates/head.php"; ?>
    <style>
        #workarea-content .workarea-content-paddings {
            height: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .list-link {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .link-custom {
            color: #165b7d;
            padding: 25px 15px;
            display: inline-flex;
            background: antiquewhite;
            border-radius: 5px;
            flex-direction: column;
            align-items: center;
            font-size: 25px;
            width: 200px;
        }

        .link-custom:hover {
            background: #e2caa9;
        }

        .link-custom img {
            width: 50px;
        }
    </style>
</head>

<body>
    <div class="mt-3" id="ms-signup">
       <div class="list-link">
           <a href="<?php echo $config['base_url'] . '/' . $config['root_folder'] . '/form/register/'; ?>" class="link-custom"><img src="./public/images/goal.png" alt='icon' />Form đăng ký MS</a>
           <a href="<?php echo $config['base_url'] . '/' . $config['root_folder'] . '/form/unregister/'; ?>" class="link-custom"><img src="./public/images/goal.png" alt='icon' />Form hủy đăng ký MS</a>
           <a href="<?php echo $config['base_url'] . '/' . $config['root_folder'] . '/form/list/'; ?>" class="link-custom"><img src="./public/images/goal.png" alt='icon' />Danh sách đơn xét duyệt</a>
       </div>
    </div>

    <?php include "templates/scripts.php"; ?>
</body>

</html>

<?php require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"; ?>