<?php

namespace Web\Model;

use RedBeanPHP\R;
use Web\App\Paginator;
use Web\App\Config;
use Web\Model\Api\NewPost;
use Web\App\Model;
use LisDev\Delivery\NovaPoshtaApi2;
use Web\Tools\Log;

class Orders extends Model
{
    const table = 'orders';

    // Дані по замовленню
    public static function getOrder($id)
    {
        return R::getRow('
            SELECT 
                `orders`.*,
                `users`.`name` AS `courier`,
                `logistics`.`name` AS `delivery`,
                `pays`.`name` AS `pay_method` 
            FROM 
                `orders`
            LEFT JOIN `users` ON (`users`.`id` = `orders`.`courier`) 
            LEFT JOIN `logistics` ON(`logistics`.`id` = `orders`.`delivery`)
            LEFT JOIN `pays` ON(`pays`.`id` = `orders`.`pay_method`)
            WHERE 
                `orders`.`id` = ?', [$id]);
    }

    // Дані для списку замовлень
    public static function orderDataByType($type)
    {
        // кількість пунктів на сторінку
        $items = Config::items();

        // початок вибірки
        $start_query = (get('page') - 1) * $items;

        $sql = '`orders`.`type` = \'' . $type . '\'';

        if (get('id'))
            $sql .= ' AND `orders`.`id` LIKE \'' . get('id') . '%\'';

        if (get('fio'))
            $sql .= ' AND `orders`.`fio` LIKE \'%' . get('fio') . '%\'';

        if (get('pay_method'))
            $sql .= " AND `orders`.`pay_method` = '" . get('pay_method') . "'";

        if (get('site'))
            $sql .= ' AND `orders`.`site` = \'' . get('site') . '\'';

        if (get('atype'))
            $sql .= ' AND `orders`.`atype` = \'' . get('atype') . '\'';

        if (get('hint'))
            $sql .= ' AND `orders`.`hint` = \'' . get('hint') . '\'';

        if (get('pay_method'))
            $sql .= ' AND `orders`.`pay_method` = \'' . get('pay_method') . '\'';

        if (get('phone'))
            $sql .= ' AND `orders`.`phone` LIKE \'' . get('phone') . '%\'';

        if (get('phone2'))
            $sql .= ' AND `orders`.`phone2`= \'' . get('phone2') . '\'';

        if (get('date'))
            $sql .= ' AND DATE(`orders`.`date_delivery`) = \'' . get('date') . '\'';

        if (isset($_GET['courier']) && is_numeric($_GET['courier']))
            $sql .= ' AND `orders`.`courier` = \'' . get('courier') . '\'';

        if (get('time_with') !== false)
            $sql .= ' AND `orders`.`time_with` >= \'' . time_to_string(get('time_with')) . '\' ';

        if (get('time_to'))
            $sql .= ' AND `orders`.`time_to` <= \'' . time_to_string(get('time_to')) . '\' ';

        if (get('region'))
            $sql .= ' AND `orders`.`street` LIKE \'%(' . preg_replace("/'/", "\'", get('region')) . ')\'';

        if (get('warehouse'))
            $sql .= " AND `orders`.`warehouse`= '{$_GET['warehouse']}'";

        if (get('street'))
            $sql .= " AND `orders`.`street` LIKE '%" . get('street') . "%'";

        if (get('full_sum'))
            $sql .= ' AND `orders`.`full_sum` LIKE \'' . get('full_sum') . '%\' ';

        if (get('liable'))
            $sql .= ' AND `orders`.`liable` = \'' . get('liable') . '\' ';

        if (get('from') && get('to'))
            $sql .= ' AND DATE(`orders`.`date`) BETWEEN \'' . get('from') . '\' AND \'' . get('to') . '\' ';

        if (get('status') !== false) {
            if (get('status') === 'open') {
                $sql .= $type == 'shop' ? ' AND `orders`.`status` = 0 ' : ' AND `orders`.`status` IN(0,1) ';
            } else if (get('status') === 'close') {
                $sql .= $type == 'shop' ? ' AND `orders`.`status` IN(1,2) ' : ' AND `orders`.`status` IN(2,3,4) ';
            } else {
                $sql .= " AND `orders`.`status` = '" . get('status') . "'";
            }
        } else {
            $sql .= $type == 'shop' ? '' : ' AND `orders`.`status` IN(0,1,4)';
        }


        $query = "
            SELECT orders.*,
                colors.description AS description,
                colors.color AS color,
                logistics.name AS delivery,
                pays.name AS pay_method,
                order_type.name AS order_type_name,
                order_type.color AS order_type_color,
                users.login AS order_type_login,
                shops.name AS shop_name,
                client_orders.client_id AS client_id
            FROM orders
            LEFT JOIN colors ON colors.id = orders.hint 
            LEFT JOIN logistics ON logistics.id = orders.delivery 
            LEFT JOIN pays ON pays.id = orders.pay_method 
            LEFT JOIN order_type ON order_type.id = orders.atype
            LEFT JOIN users ON users.id = orders.liable 
            LEFT JOIN shops ON shops.id = orders.warehouse
            LEFT JOIN client_orders ON client_orders.order_id = orders.id
            WHERE $sql
            
            GROUP BY orders.id
            ORDER BY orders.id DESC";


        $data = R::getAll("$query LIMIT $start_query, $items");


        $data = static::loadBonuses($data);
        return [
            'data' => $data,
            'paginate' => Paginator::simple('orders', $sql)
        ];
    }

    private static function loadBonuses($items)
    {
        foreach ($items as $i => $item) {
            $bonuses = R::findAll('bonuses', "`data` = {$item['id']} AND `source` = 'order'");

            $items[$i]['bonuses'] = $bonuses;
        }

        return $items;
    }

    // Дані по замовленню
    public static function getOrderById($id)
    {
        $id = (int)($id);
        $r = R::getRow("
            SELECT 
                `orders`.*, `logistics`.`name` AS `logistic_name`,
                `users`.`name` AS `courier_name`,
                `pays`.`name` AS `pay_name`,
                `colors`.`description` AS `hint_name`,
                `co`.`client_id` AS `client`
            FROM 
                `orders` 
            LEFT JOIN `logistics` ON (`logistics`.`id` = `orders`.`delivery`) 
            LEFT JOIN `users` ON(`users`.`id` = `orders`.`courier`)
            LEFT JOIN `pays` ON (`pays`.`id` = `orders`.`pay_method`)
            LEFT JOIN `colors` ON (`colors`.`id` = `orders`.`hint`)
            LEFT JOIN `client_orders` AS `co` ON(`co`.`order_id` = $id) 
            WHERE 
                `orders`.`id` = $id ");

        return get_object($r);
    }

    // Історія замовлення
    public static function get_changes_by_id($id)
    {
        $result = R::getAll('
            SELECT
                `changes`.*,
                `users`.`login` AS `login`
            FROM
                `changes`
            LEFT JOIN `users` ON(`users`.`id` = `changes`.`author`)
            WHERE
                `changes`.`id_order` = ?
            ORDER BY `changes`.`id` DESC
        ', [$id]);

        return (get_object($result));
    }

    // Товари привязані до замовлення
    public static function getProductsByOrderId($id)
    {
        return R::getAll("
            SELECT
                `products`.*,
                `pto`.`attributes` AS `attr`,
                `pto`.`id` AS `pto`,
                `pto`.`amount` AS `amount`,
                `pto`.`price` AS `price`,
                `pto`.`place` AS `place`,
                `pts`.`count` AS `count_on_storage`,
                `pts`.`storage_id` AS `storage_id`,
                `storage`.`name` AS `storage_name`
            FROM
                `product_to_order` AS `pto`
            LEFT JOIN `products` ON(`products`.`id` = `pto`.`product_id`)
            LEFT JOIN `product_to_storage` AS `pts` ON(`pts`.`product_id` = `pto`.`product_id` AND `pts`.`storage_id` = `pto`.`storage_id`)
            LEFT JOIN `storage` ON(`storage`.`id` = `pts`.`storage_id`)
            WHERE `pto`.`order_id` = ?", [$id]);
    }

    // Видалити товар з замовлення
    public static function drop_product($data)
    {
        $pto = R::load('product_to_order', $data->pto);
        if (empty($pto)) response(400, 'Товар видалений з замовлення раніше!');

        // Змінюємо вартість замовлення
        $order = R::load('orders', $pto->order_id);
        $order->full_sum -= $pto->amount * $pto->price;
        R::store($order);

        // змінюємо суму зворотньої доставки(якщо тип грошовий переказ)
        $rs = R::findOne('return_shipping', '`order_id` = ?', [$pto->order_id]);
        if (!empty($rs)) {
            if ($rs->type == 'remittance') {
                $rs->sum = $order->full_sum - $order->discount + $order->delivery_cost;
                R::store($rs);
            }
        }

        // загружаємо товар
        $product = R::load('products', $pto->product_id);

        // якщо товар комбінований
        if ($product->combine) {

            // загружаємо компоненти
            $linked = R::findAll('combine_product', 'product_id = ?', [$product->id]);

            // перебираємо кожен компонент
            foreach ($linked as $item) {

                // загружаємо безпосередньо сам компонент
                $component = R::load('products', $item->linked_id);

                // якщо компонент обліковується
                if ($component->accounted) {

                    // створюємо `pts` якщо немає
                    $pts = self::create_pts_if_not_exists($item->linked_id, $pto->storage_id);

                    // додаємо до кількості
                    $pts->count += $pto->amount * $item->combine_minus;

                    // зберігаємо
                    R::store($pts);
                }
            }
        } else {

            // якщо товар обліковується
            if ($product->accounted) {

                // створюємо `pts` якщо немає
                $pts = self::create_pts_if_not_exists($product->id, $pto->storage_id);

                // додаємо до кількості
                $pts->count += $pto->amount;

                // зберігаємо
                R::store($pts);
            }
        }

        // зберігаємо історію замовлення
        self::save_changes_log('delete_product', json(['id' => $product->id, 'name' => $product->name]), $pto->order_id);

        // зберігаємо історію товару
        self::history_product('removed_from_order', json(['order' => $pto->order_id]), $product->id);

        // Видаляємо товар з замовлення
        R::trash($pto);
    }

    // Товари в замовленні
    public static function findById($id)
    {
        return R::findAll('product_to_order', '`order_id` = ?', [$id]);
    }

    // Дані по зворотній доставці
    public static function get_return_shipping($id)
    {
        return (R::findOne('return_shipping', '`order_id` = ?', [$id]));
    }

    // Змінити тип замовлення (Самовивіз <=> Доставка)
    public static function change_type($type, $id)
    {
        $type_name = $type == 'self' ? 'Самовивіз' : 'Доставка';
        $bean = R::load('orders', $id);
        $bean->type = $type;
        R::store($bean);

        self::save_changes_log('update_type', "Змінено тип на \"$type_name\"", $id);
    }

    // Оновлення статусів доставок
    public static function updateSendingStatus()
    {
        set_time_limit(300);
        $sql = "`type` = 'sending' AND `status` = '1' AND `street` != ''";
        $count = R::count('orders', $sql);
        $post = new NewPost();
        for ($i = 0; $i <= $count; $i += 100) {
            $orders = R::findAll('orders', $sql . "LIMIT $i,100");
            self::update_statuses($post->getStatusDocuments($orders)['data']);
        }
    }

    // Оновлення статусів доставок на Новій Пошті
    private static function update_statuses($result)
    {
        foreach ($result as $item) {
            $bean = R::findOne('orders', 'street LIKE ?', ['%' . $item['Number'] . '%']);
            if ($bean != null) {
                $bean->phone2 = $item['StatusCode'];
                R::store($bean);
            }
        }
    }

    // Бонуси і штрафи по замовленню
    public static function getBonuses($id)
    {
        $result = R::getAll("
            SELECT 
                `bonuses`.*,
                `users`.`first_name` AS `first_name`,
                `users`.`last_name` AS `last_name`
            FROM 
                `bonuses`
            LEFT JOIN `users` ON(`users`.`id` = `bonuses`.`user_id`)
            WHERE
                `bonuses`.`data` = ? AND `bonuses`.`source` = 'order'
            GROUP BY `bonuses`.`id`
        ", [$id]);

        $new = get_object([]);
        foreach ($result as $item) {
            $new->{$item['user_id']} = get_object($item);
        }

        return $new;
    }

    // Оновити суму бонуса
    public static function update_bonus_sum($post)
    {
        $bonuses_bean = R::load('bonuses', $post->id);
        $date = date_parse($bonuses_bean->date);
        $binds = [$date['year'], $date['month'], $bonuses_bean->user_id];
        $sql = '`year` = ? AND `month` = ? AND `user` = ?';

        if (!R::count('work_schedule_month', $sql, $binds)) response(400, 'Неможливо редагувати!');

        $wsm = R::findOne('work_schedule_month', $sql, $binds);

        if ($bonuses_bean->type == 'bonus') {
            $sum = ($wsm->bonus - $bonuses_bean->sum) + $post->sum;
            $wsm->bonus = $sum < 0 ? 0 : $sum;
        } else {
            $sum = ($wsm->fine - $bonuses_bean->sum) + $post->sum;
            $wsm->fine = $sum < 0 ? 0 : $sum;
        }

        $bonuses_bean->sum = $post->sum;

        R::store($bonuses_bean);
        R::store($wsm);
    }

    // Створити бонус/штраф
    public static function create_user_bonus($post)
    {
        $sql = '`year` = ? AND `month` = ? AND `user` = ?';
        $binds = [date('Y'), date('m'), $post->user_id];

        if (!R::count('work_schedule_month', $sql, $binds))
            Schedule::create_schedule(date('Y'), date('m'), $post->user_id);

        $wsm = R::findOne('work_schedule_month', $sql, $binds);

        if ($post->type == 'bonus') $wsm->bonus += $post->sum; else $wsm->fine += $post->sum;

        R::store($wsm);

        $post->source = 'order';
        $post->data = $post->order_id;
        unset($post->order_id);
        self::insert($post, 'bonuses');
    }

    // Видалити бонус
    public static function delete_bonus($post)
    {
        $bonuses_bean = R::load('bonuses', $post->id);
        $date = date_parse($bonuses_bean->date);
        $binds = [$date['year'], $date['month'], $bonuses_bean->user_id];
        $sql = '`year` = ? AND `month` = ? AND `user` = ?';

        if (R::count('work_schedule_month', $sql, $binds)) {
            $wsm = R::findOne('work_schedule_month', $sql, $binds);

            if ($bonuses_bean->type == 'bonus') {
                $wsm->bonus -= $bonuses_bean->sum;
            } else {
                $wsm->fine -= $bonuses_bean->sum;
            }

            R::store($wsm);
        }

        R::trash($bonuses_bean);
    }

    // Підказки по типам замовлення
    public static function getHints($type)
    {
        return R::findAll('colors', '`type` IN(0,?)', [$type]);
    }

    // Товари для роздруковки
    public static function getProducts($id)
    {
        $pto = R::findAll('product_to_order', '`order_id` = ?', [$id]);
        $new = [];
        $places = [];
        $sum = 0;
        $i = 0;
        foreach ($pto as $item) {
            $bean = R::load('products', $item->product_id);
            $storage = R::load('storage', $item->storage_id);
            $new[$i]['articul'] = $bean->articul;
            $new[$i]['amount'] = $item->amount;
            $new[$i]['storage'] = $storage;
            $new[$i]['attributes'] = $item->attributes;
            $new[$i]['price'] = $item->price;
            $new[$i]['model'] = $bean->model;
            $new[$i]['name'] = $bean->name;
            $new[$i]['description'] = $bean->description;
            $new[$i]['identefire_storage'] = $bean->identefire_storage;
            $new[$i]['sum'] = $item->amount * $item->price;
            $sum += $item->amount * $item->price;

            $new[$i]['place'] = $item->place;
            $item->place = is_numeric($item->place) ? $item->place : '1';
            $places[$item->place]['weight']
                = isset($places[$item->place]['weight'])
                ? $places[$item->place]['weight'] + $item->amount * $bean->weight
                : $item->amount * $bean->weight;
            $places[$item->place]['volume']
                = isset($places[$item->place]['volume'])
                ? $places[$item->place]['volume'] + $item->amount * self::volume_calculator($bean->volume)
                : $item->amount * self::volume_calculator($bean->volume);


            $i++;
        }
        return get_object(['products' => $new, 'places' => $places, 'sum' => $sum]);
    }

    // Калькулятор обэму для роздруковки
    public static function volume_calculator($str)
    {
        $array = with_json($str);
        if (is_string($array) || is_float($array))
            return $array;
        if (!isset($array[0])) $array[0] = 0;
        if (!isset($array[1])) $array[1] = 0;
        if (!isset($array[2])) $array[2] = 0;

        return ($array[0] * $array[1] * $array[2]) / 1000000;
    }

    // Назва транспортної компанії для роздруковки
    public static function getDeliveryName($id)
    {
        $bean = R::load('logistics', $id);
        return $bean->name;
    }

    // Назва способу оплати для роздруковки
    public static function getPay($id)
    {
        return (object)R::findOne('return_shipping', '`order_id` = ?', [$id]);
    }

    // Сума замовлення для роздруковки
    public static function getSum($order)
    {
        $pto = R::findAll('product_to_order', '`order_id` = ?', [$order->id]);
        $sum = 0;

        foreach ($pto as $item) {
            $sum += $item->amount * $item->price;
        }

        $sum += $order->delivery_cost;
        $sum -= $order->discount;

        return $sum;
    }

    // Получаємо назву міста по Ref Нової пошти
    private static function prepare_city($data)
    {
        if (isset($data->city)) {
            $new_post = new NewPost();
            if (!isset($data->warehouse)) {
                $data->city = $new_post->getNameCityByRef($data->city);
            } else {
                $temp = $new_post->get_address($data->city, $data->warehouse);
                $data->city = $temp['city'];
                $data->warehouse = $temp['warehouse'];
            }
        }

        return $data;
    }

    // Пишимо історію замовлення(при створенні)
    private static function save_original($data, $products, $id, $return_shipping = false)
    {
        // Получаємо людино-подібну назву міста і складу(якщо нова пошта)
        if (isset($data->delivery)) {
            $delivery_company = R::load('logistics', $data->delivery);
            if ($delivery_company->name == 'НоваПошта') $data = self::prepare_city($data);
        }

        // видаляємо
        if (isset($data->form_delivery) && $data->form_delivery == 'imposed')
            unset($data->imposed);

        // Заміняємо ідентифікатори на значення
        $arr = ['courier' => 'users', 'pay_method' => 'pays', 'delivery' => 'logistics', 'site' => 'sites'];
        foreach ($arr as $key => $table) if (isset($data->$key)) $data->$key = (R::load($table, $data->$key))->name;

        // заміняємо ключі значеннями
        $arr = ['imposed' => ['sender' => 'Відправник', 'recipient' => 'Отримувач'],
            'pay_delivery' => ['sender' => 'Відправник', 'recipient' => 'Отримувач'],
            'form_delivery' => ['imposed' => 'Наложений платіж', 'on_the_card' => 'Безготівкова']];
        foreach ($arr as $key => $value) if (isset($data->$key)) $data->$key = $value[$data->$key];

        // Визначаємо підказку
        if (isset($data->hint) && $data->hint != 0) {
            $temp = R::load('colors', $data->hint);
            $data->hint = '<span style="color: #' . $temp->color . '">' . $temp->description . '</span>';
        }

        // додавання назв товарів у історію
        foreach ($products as $i => $value) {
            $products->$i->name = (R::load('products', $value->id))->name;
            $products->$i->storage_name = (R::load('storage', $value->storage))->name;
        }
        $data->products = $products;

        // зворотня доставка
        if ($return_shipping !== false) {
            $data->return_shipping = new \stdClass();
            $data->return_shipping->type = $return_shipping->type;
            if ($return_shipping->type == 'remittance') $data->return_shipping->payer = $return_shipping->payer;
        }

        // видаляємо порожні поля
        foreach ($data as $key => $value) if ($value == '') unset($data->$key);

        self::save_changes_log('original', json($data), $id);
    }

    private static function products($products, $id)
    {
        $sum = 0;
        foreach ($products as $product) {
            // Додаємо запис в історію товару
            self::history_product('add_to_order', json([
                'order_id' => $id,
                'amount' => $product->amount,
                'price' => $product->price]), $product->id);

            // списуємо товар зі складу
            self::take_product_from_warehouse($product);

            // привязуємо товар до замовлення і зберігаємо
            $bean = R::xdispense('product_to_order');
            $bean->order_id = $id;
            $bean->product_id = $product->id;
            $bean->attributes = isset($product->attributes) ? json($product->attributes) : '{}';
            $bean->amount = $product->amount;
            $bean->price = $product->price;
            $bean->place = isset($product->place) ? $product->place : 1;
            $bean->storage_id = $product->storage;
            R::store($bean);

            // рахуємо суму замовлення
            $sum += $product->amount * $product->price;
        }

        // загружаємо створене замовлення
        $bean = R::load('orders', $id);

        // міняємо його суму
        $bean->full_sum = $sum + $bean->delivery_cost - $bean->discount;

        // зберігаємо
        R::store($bean);
    }

    // списати товар зі складу
    private static function take_product_from_warehouse($product)
    {
        $bean = R::load('products', $product->id);

        // якщо товар комбінований
        if ($bean->combine) {

            // загружаємо аліаси компонентів
            $components = R::findAll('combine_product', 'product_id = ?', [$product->id]);

            foreach ($components as $component) {

                // загружаємо pts компонента
                $pts = self::create_pts_if_not_exists($component->linked_id, $product->storage);

                // віднімаємо з складу
                $pts->count -= $product->amount * $component->combine_minus;

                // додаємо до закупки якщо <= 2
                self::create_purchase($pts, $product->amount * $component->combine_minus);

                // зберігаємо
                R::store($pts);
            }
        } elseif (!$bean->combine && $bean->accounted) { // якщо товар одиничний і обіковий

            // загружаємо pts
            $pts = self::create_pts_if_not_exists($product->id, $product->storage);

            // віднімаємо з складу
            $pts->count -= $product->amount;

            // додаємо до закупки якщо <= 2
            self::create_purchase($pts, $product->amount);

            // зберігаємо
            R::store($pts);
        }
    }

    // Створення замовлень
    // Відправка
    public static function createSending($data, $products, $return_shipping)
    {
        // створюємо замовлення
        $bean = R::dispense('orders');
        foreach ($data as $k => $v) $bean->$k = trim($v);
        $bean->date = date('Y-m-d H:i:s');
        $bean->author = user()->id;
        $id = R::store($bean);

        if (isset($_POST['client_id']))
            self::add_client_to_order($_POST['client_id'], $id);

        // загружаємо тільки що створене замовлення
        $order = R::load('orders', $id);

        // кидаємо замовлення в сесію
        $_SESSION['order'] = $order;

        // створення звіту по предоплаті замовлення
        if ($order->prepayment != 0) Reports::createOrderPrepayment($order->prepayment, $order->id);

        // додавання товарів до замовлення
        self::products($products, $id);

        // створення зворотньої доставки
        $rs = R::xdispense('return_shipping');
        $order = R::load('orders', $id);
        foreach ($return_shipping as $key => $value) $rs->$key = $value;
        $rs->sum = $order->full_sum - $order->discount + $order->delivery_cost;
        $rs->order_id = $_SESSION['order']->id;
        R::store($rs);


        // створюємо оригінал історії замовлення
        self::save_original($data, $products, $id, $return_shipping);

        return $id;
    }

    // Самовивіз
    public static function createSelf($data, $products)
    {
        $bean = R::dispense('orders');

        foreach ($data as $k => $v) $bean->$k = $v;
        $bean->date = date('Y-m-d H:i:s');
        $bean->author = user()->id;

        $id = R::store($bean);

        if (isset($_POST['client_id']))
            self::add_client_to_order($_POST['client_id'], $id);

        // загружаємо тільки що створене замовлення
        $order = R::load('orders', $id);

        // кидаємо замовлення в сесію
        $_SESSION['order'] = $order;

        // створення звіту по предоплаті замовлення
        if ($order->prepayment != 0) Reports::createOrderPrepayment($order->prepayment, $order->id);

        self::products($products, $id);
        self::save_original($data, $products, $id);

        return $id;
    }

    // Доставка
    public static function createDelivery($data, $products)
    {
        $bean = R::dispense('orders');

        foreach ($data as $k => $v) {
            if ($k == 'time_with' || $k == 'time_to')
                $bean->$k = time_to_string($v);
            elseif ($k != 'client_id')
                $bean->$k = $v;
        }

        $bean->date = date('Y-m-d H:i:s');
        $bean->author = user()->id;

        $id = R::store($bean);

        if (isset($_POST['client_id']))
            self::add_client_to_order($_POST['client_id'], $id);

        // звіт по предоплаті замовлення
        if ($data->prepayment != 0) Reports::createOrderPrepayment($data->prepayment, $id);

        // товари
        self::products($products, $id);

        // історрія замовлення
        self::save_original($data, $products, $id);

        return $id;
    }

    // Магазин
    public static function createShop($data, $products)
    {
        $bean = R::dispense('orders');

        foreach ($data as $k => $v) $bean->$k = $v;
        $bean->date = date('Y-m-d H:i:s');
        $bean->author = user()->id;
        $bean->status = 0;

        $id = R::store($bean);

        self::products($products, $id);

        self::save_original($data, $products, $id);

        return $id;
    }


    // Додати клієнта до замовлення
    private static function add_client_to_order($client_id, $order_id)
    {
        $bean = R::xdispense('client_orders');

        $bean->client_id = $client_id;
        $bean->order_id = $order_id;

        R::store($bean);
    }

    private static function create_purchase($pts, $amount = 1)
    {
        return;
        $product = R::load('products', $pts->product_id);
        if ($pts->count <= 2) {
            Purchases::create((object)[
                'manufacturer_id' => $product->manufacturer,
                'products' => [
                    [
                        'id' => $product->id,
                        'amount' => $amount,
                        'price' => $product->procurement_costs,
                        'course' => app()->course
                    ]
                ],
                'sum' => $product->procurement_costs * app()->course,
                'comment' => 'Створено автоматично!!!',
                'storage_id' => $pts->storage_id
            ]);
        }
    }

    private static function create_return_shipping($post, $bean)
    {
        if (empty($bean)) {
            $sql = "INSERT INTO return_shipping SET order_id = ?, type = 'none'";
            R::exec($sql, [$post->id]);
            return R::findOne('return_shipping', '`order_id` = ?', [$post->id]);
        } else {
            return $bean;
        }
    }

    public static function update_return_shipping($post)
    {
        $bean = R::findOne('return_shipping', '`order_id` = ?', [$post->id]);
        $bean = self::create_return_shipping($post, $bean);

        $id = $post->id;
        unset($post->id);

        $history = [];
        foreach ($post as $k => $v) {
            if ($bean->$k != $v) {
                $history[$k] = $v;
            }
            $bean->$k = $v;
        }

        if (count($history) > 0)
            self::save_changes_log('update_return_shipping', json($history), $id);

        R::store($bean);
    }

    private static function productsSum($order_id, $products)
    {
        $bean = R::load('orders', $order_id);
        if ($bean->type == 'sending') {
            $sum = 0;
            $products = get_object($products);
            foreach ($products as $item) {
                $sum += $item->amount * $item->price;
            }

            $return_shipping = R::findOne('return_shipping', '`order_id` = ?', [$order_id]);
            $return_shipping = self::create_return_shipping((object)['id' => $order_id], $return_shipping);
            $return_shipping->sum = $sum - $bean->discount + $bean->delivery_cost;
            R::store($return_shipping);
        }
    }

    // Оновлення контактів
    public static function update_contacts($post)
    {
        $bean = R::load('orders', $post->id);

        $history = [];
        foreach ($post as $k => $v) {
            // Наповнюємо масив історії
            if ($bean->$k != $v) $history[$k] = $v;
            // Оновлємо дані
            $bean->$k = trim($v);
        }

        // Зберігаємо історію
        if (count($history) > 0) self::save_changes_log('update_contact', json($history), $post->id);

        R::store($bean);
    }

    // Оновлення статусу
    public static function update_status($post)
    {
        $bean = R::load('orders', $post->id);
        $bean->status = $post->status;
        if ($bean->type != 'shop') {
            if ($post->status == 2 || $post->status == 3)
                self::return_products($post->id);
        } else {
            if ($post->status == 2)
                self::return_products($post->id);
        }
        R::store($bean);

        $statuses = OrderSettings::statuses($bean->type);
        $data = 'Новий статус: "' . $statuses[$post->status]->text . '"';
        self::save_changes_log('update_status', $data, $post->id);
    }

    // Оновлення адреси
    public static function update_address($post)
    {
        $bean = R::load('orders', $post->id);

        $history = [];

        // Історія - відділення
        if ($bean->type == 'sending') {
            $delivery_company = R::load('logistics', $bean->delivery);
            if ($delivery_company->name == 'НоваПошта') {
                $history = self::history_address($bean, $post);
            }
        }

        // Іторія - Адреса
        if (isset($post->address)) {
            if ($bean->address != $post->address) {
                $history['address'] = $post->address;
            }
        }

        // Іторія - Адреса
        if (isset($post->street)) {
            if ($bean->street != $post->street) {
                $history['street'] = $post->street;
            }
        }

        // Дані в таблиці
        foreach ($post as $k => $v)
            $bean->$k = trim($v);

        self::save_changes_log('update_address', json($history), $post->id);

        R::store($bean);
    }

    // Оновлення загальної інформації
    public static function update_working($post)
    {
        $bean = R::load('orders', $post->id);

        $history = [];

        // Зміна Курєра
        if (isset($post->courier)) {
            if ($bean->courier != $post->courier) {
                $history['courier'] = $post->courier == 0 ? 'Не вибрано' : user($post->courier)->name;
            }
        }

        // Зміна транспортної компанії
        if (isset($post->delivery)) {
            if ($bean->delivery != $post->delivery) {
                $delivery = parent::getOne($post->delivery, 'logistics');
                $history['delivery'] = $delivery->name;
            }
        }

        // Зміна сайту
        if (isset($post->site)) {
            if ($bean->site != $post->site) {
                $site = parent::getOne($post->site, 'sites');
                $history['site'] = $site->name;
            }
        }

        // Зміна підказки
        if (isset($post->hint)) {
            if ($bean->hint != $post->hint) {
                $hint = parent::getOne($post->hint, 'colors');
                $history['hint'] = '<span style="color: #' . $hint->color . '">' . $hint->description . '</span>';
            }
        }

        // Зміна решти полів
        // Дата доставки, коментар, купон, градація по часу доставки
        foreach (['date_delivery', 'comment', 'coupon', 'time_with', 'time_to'] as $k)
            if (isset($post->$k)) if ($bean->$k != $post->$k) $history[$k] = $post->$k;

        // Перетворення часу доставки
        if (isset($post->time_with))
            $post->time_with = time_to_string($post->time_with);

        if (isset($post->time_to))
            $post->time_to = time_to_string($post->time_to);

        // Оновлення даних
        foreach ($post as $k => $v) $bean->$k = $v;

        self::save_changes_log('update_working', json($history), $post->id);

        R::store($bean);
    }

    // Оновлення інформації про оплату
    public static function update_pay($post)
    {
        $bean = R::load('orders', $post->id);
        $history = [];

        if (isset($post->pay_method)) {
            if ($bean->pay_method != $post->pay_method) {
                $pay = R::load('pays', $post->pay_method);
                $history['pay_method'] = $pay->name;
                $bean->pay_method = $post->pay_method;
            }
        }

        // Заносимо в звіт дані про предоплату
        if (isset($post->prepayment))
            self::prepaymentOrder($post->prepayment, $post->id);

        // Оплата: ??, Оплата доставки, ??, Предоплата
        foreach (['form_delivery', 'pay_delivery', 'imposed', 'prepayment'] as $k) {
            if (isset($post->$k)) {
                if ($bean->$k != $post->$k) {
                    $history[$k] = $post->$k;
                }
                $bean->$k = $post->$k;
            }
        }

        self::save_changes_log('update_pay', json($history), $post->id);

        R::store($bean);
    }

    private static function history_address($bean, $post)
    {
        $history = [];
        $new_post = new NewPost();

        if ($bean->city != $post->city) {
            $history['city'] = $new_post->getNameCityByRef($post->city);
        }

        $warehouses = $new_post->search_warehouses($post->city);
        foreach ($warehouses['data'] as $warehouse) {
            if ($warehouse['Ref'] == $post->warehouse) {
                $history['warehouse'] = $warehouse['Description'];
                break;
            }
        }

        return $history;
    }

    private static function prepaymentOrder($sum, $id)
    {
        $report = new Reports();
        if (R::count('reports', "`data` = ? AND `type` = ?", [$id, 'order_prepayment'])) {
            if ($sum == 0) {
                $report->deleteOrderPrepayment($id);
            } else {
                $report->updateOrderPrepayment($sum, $id);
            }
        } else {
            $report->createOrderPrepayment($sum, $id);
        }
    }

    public static function update_products($products, $data, $order_id)
    {
        $sum = 0;
        foreach ($products as $product) {
            if ($product->pto != 0) self::update_product($product->pto, $product);
            else self::add_product($product->id, $order_id, $product);

            $sum += $product->amount * $product->price;
        }

        $history = (object)[];
        $bean = R::load('orders', $order_id);

        $old_sum = $bean->full_sum;

        if ($bean->discount != $data->discount) {
            $history->discount = "Знижка <b style='color: red'>$bean->discount</b> => <b style='color: blue'>$data->discount</b>";
            $bean->discount = $data->discount;
        }

        if ($bean->delivery_cost != $data->delivery_cost) {
            $history->delivery_cost = "Ціна доставки <b style='color: red'>$bean->delivery_cost</b> => <b style='color: blue'>$data->delivery_cost</b>";
            $bean->delivery_cost = $data->delivery_cost;
        }

        $bean->full_sum = $sum + $data->delivery_cost - $data->discount;

        self::productsSum($order_id, $products);

        if (my_count($history) > 0) {
            $history->full_sum = "Сума <b style='color: red'>" . nf($old_sum) . "</b> => <b style='color: blue'>" . nf($bean->full_sum) . "</b>";
            self::save_changes_log('update_price', json($history), $order_id);
        }

        R::store($bean);
    }

    public static function create_pts_if_not_exists($product_id, $storage_id)
    {
        if (!R::count('product_to_storage', '`product_id` = ? AND `storage_id` = ?', [$product_id, $storage_id])) {
            $pts = R::xdispense('product_to_storage');
            $pts->product_id = $product_id;
            $pts->storage_id = $storage_id;
            $pts->count = 0;
            R::store($pts);

            return $pts;
        } else {
            return R::findOne('product_to_storage', '`product_id` = ? AND `storage_id` = ?', [$product_id, $storage_id]);
        }
    }

    private static function update_product($pto, $product)
    {
        // Загружаємо `pto`
        $pto = R::load('product_to_order', $pto);

        // масив з змінами
        $history = (object)[];

        // загружаємо безпосередньо сам товар
        $bean = R::load('products', $product->id);

        // якщо кількість товару змінилась
        if ($product->amount != $pto->amount) {

            // Зберігаємо дані в історію
            $history->amount = "Кількість <b style='color: red'>$pto->amount</b> => <b style='color: blue;'>$product->amount</b>";

            // якщо товар комбінований
            if ($bean->combine) {

                // Загружаємо аліаси компонентів
                $components = R::findAll('combine_product', '`product_id` = ?', [$product->id]);

                // перебираємо всі аліаси компонентів
                foreach ($components as $component) {

                    // Загружаємо безпосередньо сам компонент
                    $p = R::load('products', $component->linked_id);

                    // якщо компонент обліковується
                    if ($p->accounted) {

                        // Загружаємо аліас компонента на складі
                        $pts = self::create_pts_if_not_exists($p->id, $product->storage);

                        // Міняємо кількість товару на складі
                        $pts->count += ($pto->amount - $product->amount) * $component->combine_minus;

                        // Створюємо закупку якщо товару менше 2 одиниць
                        self::create_purchase($pts);

                        // зберігаємо кількість на складі
                        R::store($pts);
                    }
                }
            } elseif (!$bean->combine && $bean->accounted) { // якщо товар одиничний і обліковий

                // Загружаємо `pts`
                $pts = self::create_pts_if_not_exists($product->id, $product->storage);

                // Змінюємо кількість на складі
                $pts->count += $pto->amount - $product->amount;

                // створюємо закупку якщо товару менше 2х одиниць
                self::create_purchase($pts); // create purchase if count on storage <= 2

                // Зберігаємо кількість на складі
                R::store($pts);
            }

            $pto->amount = $product->amount;
        }

        // змінюємо номер місця і записуємо в історію
        if (isset($product->place) && $product->place != $pto->place) {
            $history->place = "Місце <b style='color: red'>$pto->place</b> => <b style='color: blue'>$product->place</b>";
            $pto->place = $product->place;
        }

        // змінюємо ціну і записуємо в історію
        if ($product->price != $pto->price) {
            $history->price = "Ціна <b style='color: red'>$pto->price</b> => <b style='color: blue'>$product->price</b>";
            $pto->price = $product->price;
        }

        // змінюємо атрибути і записуємо в історію
        if (isset($product->attributes)) $pto->attributes = json($product->attributes);

        // якщо історія не порожня то записуємо в базу даних
        if (my_count($history) > 0) {
            $history->product_name = $bean->name;
            $history->product_id = $bean->id;
            self::save_changes_log('update_product', json($history), $pto->order_id);

            unset($history->product_name, $history->product_id);
            $history->order = $pto->order_id;
            self::history_product('update_in_order', json($history), $product->id);
        }

        // Зберігаємо `pto`
        R::store($pto);
    }

    private static function add_product($product_id, $order_id, $product)
    {
        // Привязуємо товар до замовлення
        $bean = R::xdispense('product_to_order');
        $bean->order_id = $order_id;
        $bean->product_id = $product_id;
        $bean->attributes = isset($product->attributes) ? json($product->attributes) : '{}';
        $bean->price = $product->price;
        $bean->amount = $product->amount;
        $bean->storage_id = $product->storage;
        if (isset($product->place)) $bean->place = $product->place;
        R::store($bean);

        // загружаємо товар
        $bean = R::load('products', $product_id);

        if ($bean->combine) { // Якщо товар комбінований

            // загружаємо аліаси компонентів
            $linked = R::findAll('combine_product', 'product_id = ?', [$bean->id]);

            // перебираємо кожен аліас
            foreach ($linked as $p) {

                // загруєаємо безпосередньо сам компонент
                $component = R::load('products', $p->linked_id);

                // якщо компонент обліковується
                if ($component->accounted) {

                    // додаємо компонент на склад якщо його там немає і загружаємо
                    $bean2 = self::create_pts_if_not_exists($p->linked_id, $product->storage);

                    // віднімаємо з складу кількість
                    $bean2->count -= $p->combine_minus * $product->amount;

                    // якщо товару менше 2 то створюємо закупку
                    self::create_purchase($bean2);

                    // Зберігаємо кількість на складі
                    R::store($bean2);
                }
            }

        } elseif (!$bean->combine && $bean->accounted) { // якщо товар не комбінований і обліковий

            // Загружаєм аліас
            $alias = self::create_pts_if_not_exists($bean->id, $product->storage);

            // Віднімаємо зі складу кількість
            $alias->count -= $product->amount;

            // якщо товару менше 2 то створюємо закупку
            self::create_purchase($alias);

            // зберігаємо кількість на складі
            R::store($alias);
        }

        // зберігаємо дані в історію замовлення(додано товар)
        $product->id = $product_id;
        $product->name = $bean->name;

        $storage = R::load('storage', $product->storage);
        $product->storage_name = $storage->name;
        self::save_changes_log('add_product', json($product), $order_id);

        // зберігаємо дані в історію товару(додано товар в замовлення)
        $product->order_id = $order_id;
        unset($product->name, $product->pto, $product->place);
        self::history_product('add_to_order', json($product), $product_id);
    }

    public static function history_product($type, $data, $product_id)
    {
        $bean = R::xdispense('history_product');

        $bean->product = $product_id;
        $bean->type = $type;
        $bean->data = $data;
        $bean->date = date('Y-m-d H:i:s');
        $bean->author = user()->id;

        R::store($bean);
    }

    // Повернення товарів на склад при видаленні замовлення
    private static function return_products($id): void
    {
        // загружаємо всі товари замовлення
        $pto = R::findAll('product_to_order', '`order_id` = ?', [$id]);

        // перебираємо кожен товар замовлення
        foreach ($pto as $item) {

            // загружаємо безпосередньо сам товар
            $product = R::load('products', $item->product_id);

            // якщо товар обліковий і одиничний
            if ($product->accounted && !$product->combine) {
                $pts = self::create_pts_if_not_exists($item->product_id, $item->storage->id);
                $pts->count += $item->amount;
                R::store($pts);

                // якщо товар комбінований
            } else if ($product->combine) {

                // загружаємо всі компоненти товара
                $linked = R::findAll('combine_product', 'product_id = ?', [$item->product_id]);

                // перебираємо всі компоненти товару
                foreach ($linked as $component) {
                    $pts_c = self::create_pts_if_not_exists($component->linked_id, $item->storage_id);
                    $pts_c->count += $component->combine_minus * $item->amount;
                    R::store($pts_c);
                }
            }

            // обнуляємо кількість товару в замовленні
            $item->amount = 0;
            R::store($item);
        }
    }

    // Запис в історію замовлення
    private static function save_changes_log($type, $data, $id)
    {
        $bean = R::dispense('changes');

        $bean->type = $type;
        $bean->data = $data;
        $bean->id_order = $id;
        $bean->date = date('Y-m-d H:i:s');
        $bean->author = user()->id;

        R::store($bean);
    }

    public static function update_courier($post)
    {
        $bean = R::load('orders', $post->id);

        $old = $bean->courier == '0' ? 'Не вибрано' : user($bean->courier)->name;
        $new = $post->courier == '0' ? 'Не вибрано' : user($post->courier)->name;

        $bean->courier = $post->courier;

        R::store($bean);

        $history = "<span><i style='color: red;'>$old</i> => <i style='color: blue;'>$new</i></span>";

        self::save_changes_log('update_courier', $history, $post->id);
    }

    public static function export($id)
    {
        $order = R::load('orders', $id);

        $np = new NovaPoshtaApi2(NEW_POST_KEY);

        $senderInfo = $np->getCounterparties('Sender', 1, '', '');

        if (!isset($senderInfo['data'][0])) response(500, 'Не знайдений контрагент!');

        $sender = $senderInfo['data'][0];
        $senderWarehouses = $np->getWarehouses('8d5a980d-391c-11dd-90d9-001a92567626');

        $warehouse = 28;
        foreach ($senderWarehouses['data'] as $key => $w) {
            if ($w['Number'] == '30') $warehouse = $key;
        }


        $senderContacts = $np
            ->model('Counterparty')
            ->method('getCounterpartyContactPersons')
            ->params([
                'Ref' => $sender['Ref'],
                'Page' => 1
            ])
            ->execute();

        $recipientArea = $np
            ->model('Address')
            ->method('getCities')
            ->params([
                'Ref' => $order->city,
                "Page" => "1"
            ])
            ->execute();

        $payer_type = $order->pay_delivery == 'recipient' ? 'Recipient' : 'Sender';
        $payment_method = $order->form_delivery == 'on_the_card' ? 'NonCash' : 'Cash';
        $weight = self::getWeight($id);
        $costs = $order->full_sum - $order->prepayment;

        if ($costs <= 0) $costs = 0.1;

        $data = [
            'NewAddress' => 1,
            'PayerType' => $payer_type,
            'PaymentMethod' => $payment_method,
            'CargoType' => 'Cargo',
            'VolumeGeneral' => '',
            'Weight' => $weight,
            "ServiceType" => "WarehouseWarehouse",
            "Description" => "Сувеніри",
            'Cost' => $costs,
            'CitySender' => '8d5a980d-391c-11dd-90d9-001a92567626',
            'Sender' => $sender['Ref'],
            "SenderAddress" => $senderWarehouses['data'][$warehouse]['Ref'],
            "ContactSender" => $senderContacts['data'][0]['Ref'],
            "SendersPhone" => $senderContacts['data'][0]['Phones'],
            "RecipientCityName" => $recipientArea['data'][0]['Description'],
            "CityRef" => $order->city,
            "RecipientAddress" => $order->warehouse,
            'RecipientArea' => '',
            "RecipientAreaRegions" => '',
            "RecipientAddressName" => '',
            "RecipientHouse" => '',
            "RecipientFlat" => '',
            "RecipientName" => trim($order->fio),
            "RecipientType" => "PrivatePerson",
            "RecipientsPhone" => get_number_world_format(get_number($order->phone)),
            "DateTime" => date('d.m.Y')
        ];

        $data = self::getPlaces($data, $id);

        $data = self::getBackwardDeliveryData($data, $order);

        // Отримання результату від нової пошти
        $result = $np->
        model('InternetDocument')
            ->method('save')
            ->params($data)
            ->execute();

        if ($result['success'] == 1) {
            $order->street = $result['data'][0]['IntDocNumber'];

            R::store($order);
            return 1;
        } else {

            unset ($result['success'],
                $result['data'],
                $result['info'],
                $result['messageCodes'],
                $result['infoCodes']);

            $result['order_id'] = $order->id;
            $result['manager'] = user()->id;
            $result['date'] = date('Y-m-d H:i:s');

            // записуємо помилку в лог
            (new Log())->appendNewPostLog(json($result));

            return 0;
        }
    }

    public static function getBackwardDeliveryData($data, $order)
    {
        $back = R::findOne('return_shipping', '`order_id` = ?', [$order->id]);

        if ($back->type == 'remittance') {
            $payer_type = $back->payer == 'receiver' ? 'Recipient' : 'Sender';
            $data['BackwardDeliveryData'][] = [
                'PayerType' => $payer_type,
                'CargoType' => 'Money',
                'RedeliveryString' => $order->full_sum
            ];
        }

        return $data;
    }

    public static function getPlaces($data, $id)
    {
        $pto = R::findAll('product_to_order', '`order_id` = ?', [$id]);

        $item = ['volumetricVolume' => 0];
        $temp = [];
        foreach ($pto as $item) {

            $product = R::load('products', $item->product_id);

            $volume = Orders::volume_calculator($product->volume);

            if (!isset($temp[$item->place]['volumetricVolume']))
                $temp[$item->place]['volumetricVolume'] = 0;
            if (!isset($temp[$item->place]['weight']))
                $temp[$item->place]['weight'] = 0;


            $temp[$item->place]['volumetricVolume'] += $volume * $item->amount;
            $temp[$item->place]['weight'] += $product->weight * $item->amount;
        }
        if (count($temp) == 1) {
            foreach ($temp as $item) {
                $data['OptionsSeat'][] = $item;
            }

            $data['SeatsAmount'] = 1;
            $data['VolumeGeneral'] = $item['volumetricVolume'];

        } else {
            foreach ($temp as $item) {
                $data['OptionsSeat'][] = $item;
            }

            $data['SeatsAmount'] = count($temp);
        }

        return $data;
    }

    public static function getWeight($id)
    {
        $pto = R::findAll('product_to_order', '`order_id` = ?', [$id]);

        $s = 0;
        foreach ($pto as $item) {
            $product = R::load('products', $item->product_id);
            $s += $item->amount * $product->weight;
        }

        return $s;
    }

    public static function getImages($id)
    {
        return R::findAll('order_images', '`order_id` = ? ORDER BY `id` DESC', [$id]);
    }

    public static function search_clients($post)
    {
        return R::findAll('clients', '`name` LIKE ?', ["%$post->fio%"]);
    }
}
