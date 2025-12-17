<?php
$title = "Dashboard";

ob_start();
?>

<h4>Quản lý phòng trọ</h4>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Khu vực</th>
            <th>Số phòng</th>
            <th>Tầng</th>
            <th>Diện tích</th>
            <th>Giá tiền</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Hai Bà Trưng</td>
            <td>301</td>
            <td>3</td>
            <td>15.5</td>
            <td>1.500.000</td>
        </tr>
    </tbody>
</table>

<?php
$content = ob_get_clean();
include 'layout2/theme.php';
