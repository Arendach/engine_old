<?php if ($order->comment != '') { ?>
    <table class="table table-bordered" style="margin-bottom: -10px">
        <tr>
            <td class="right">Коментар:</td>
            <td colspan="4"><?= preg_replace('/\n/', '<br>', $order->comment) ?></td>
        </tr>
    </table>
<?php } ?>

<?php if($order->type == 'sending'){ ?>
    <table class="table table-bordered" style="margin-bottom: -10px">
        <tr>
            <td>Платник доставки</td>
            <td><?= $order->pay_delivery == 'sender' ? 'Відпрвник' : 'Отримувач' ?></td>
        </tr>
        <tr>
            <td>Грошовий переказ</td>
            <td><?= isset($return_shipping->type) && $return_shipping->type == 'remittance' ? 'Є' : 'Немає' ?></td>
        </tr>
    </table>   
<?php } ?>

<table class="table table-bordered">
    <tr>
        <td>Товар</td>
        <td>Склад</td>
        <td>Кількість</td>
        <td>Ціна</td>
        <td>Сума</td>
    </tr>
    <?php foreach (get_object($products) as $item) { ?>
        <tr>
            <td><?= $item->articul ?> <?= $item->name ?></td>
            <td><?= $item->storage_name ?></td>
            <td><?= $item->amount ?></td>
            <td><?= number_format($item->price, 2) ?></td>
            <td><?= number_format($item->amount * $item->price, 2) ?></td>
        </tr>
    <?php } ?>
    <tr>
        <td colspan="3"></td>
        <td class="right">Знижка:</td>
        <td><?= number_format($order->discount, 2) ?></td>
    </tr>
    <tr>
        <td colspan="3"></td>
        <td class="right"> Доставка:</td>
        <td><?= number_format($order->delivery_cost, 2) ?></td>
    </tr>
    <tr>
        <td colspan="3"></td>
        <td class="right">Загальна сума:</td>
        <td><?= number_format($order->full_sum, 2) ?></td>
    </tr>
</table>