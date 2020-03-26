<?php

function element($key, $data = [])
{
    extract($data);

    if ($key == 'status') { ?>
        <div class="form-group">
            <label for="status" class="control-label col-md-4">Статус <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <select id="status" class="form-control status_field" name="status">
                    <?php foreach (\Web\Model\OrderSettings::statuses($type) as $k => $item) { ?>
                        <option <?= $k == $status ? 'selected' : '' ?> value="<?= $k ?>">
                            <?= $item->text ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php }

    if ($key == 'date_delivery') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="date_delivery">Дата доставки <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <input id="date_delivery" required name="date_delivery" type="date" class="form-control"
                       value="<?= $date_delivery ?>">
            </div>
        </div>
    <?php }

    if ($key == 'address') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="address">Адреса</label>
            <div class="col-md-5">
                <input id="address" name="address" class="form-control" value="<?= htmlspecialchars($address) ?>">
            </div>
        </div>
    <?php }

    if ($key == 'pay_method') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="pay_method">
                Варіант оплати <?= isset($required) ? '<i class="text-danger">*</i>' : '' ?>
            </label>
            <div class="col-md-5">
                <select required id="pay_method" name="pay_method" class="form-control">
                    <?php if (isset($empty)){ ?>
                        <option value="0"></option>
                    <?php } ?>
                    <?php foreach (Web\App\Model::getAll('pays') as $item) { ?>
                        <option <?= $item->id == $pay_method ? 'selected' : '' ?> value="<?= $item->id ?>">
                            <?= $item->name ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php }

    if ($key == 'fio') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="fio">Імя <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <input id="fio" required name="fio" class="form-control" value="<?= htmlspecialchars($fio); ?>">
            </div>
        </div>
    <?php }

    if ($key == 'phone') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="phone">Номер телефону <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <input pattern="[0-9]{3}-[0-9]{3}-[0-9]{2}-[0-9]{2}" id="phone" required name="phone"
                       class="form-control" value="<?= htmlspecialchars($phone) ?>">
            </div>
        </div>

    <?php }

    if ($key == 'phone2') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="phone2">Додатковий номер телефону</label>
            <div class="col-md-5">
                <input id="phone2" name="phone2" class="form-control" value="<?= htmlspecialchars($phone2) ?>">
            </div>
        </div>
    <?php }

    if ($key == 'email') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="email">E-mail</label>
            <div class="col-md-5">
                <input id="email" name="email" type="email" class="form-control"
                       value="<?= htmlspecialchars($email) ?>">
            </div>
        </div>
    <?php }

    if ($key == 'button') { ?>
        <div class="form-group">
            <div class="col-md-4"></div>
            <div class="col-md-5">
                <button class="btn btn-primary">Оновити</button>
            </div>
        </div>
    <?php }

    if ($key == 'hint') { ?>
        <div class="form-group">
            <label for="hints" class="col-md-4 control-label">
                <?php if (isset($type) && $type == 'sending') { ?>
                    <span style="color: red">Підказка</span> <i class="text-danger">*</i>
                <?php } else { ?>
                    Підказка
                <?php } ?>
            </label>
            <div class="col-md-5">
                <select required id="hint" name="hint" class="form-control">
                    <?php if ($type != 'sending') { ?>
                        <option value="0"></option>
                    <?php } ?>
                    <?php foreach (\Web\Model\Orders::getHints($type) as $item) { ?>
                        <option <?= htmlspecialchars($hint) == $item->id ? 'selected' : ''; ?>
                                value="<?= $item->id; ?>">
                            <?= $item->description; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php }

    if ($key == 'time_with') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="time_with">Градація по часу доставки</label>
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-addon">ВІД</span>
                    <input name="time_with" id="time_with" class="form-control"
                           value="<?= string_to_time(htmlspecialchars($time_with)) ?>">
                </div>

            </div>
        </div>
    <?php }

    if ($key == 'time_to') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="time_to"></label>
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-addon">ДО</span>
                    <input name="time_to" id="time_to" class="form-control"
                           value="<?= string_to_time(htmlspecialchars($time_to)) ?>">
                </div>
            </div>
        </div>
    <?php }

    if ($key == 'courier') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="courier">Курєр</label>
            <div class="col-md-5">
                <select id="courier" name="courier" class="form-control">
                    <option <?= $status != 0 ? 'disabled' : '' ?> <?= $courier == '0' ? 'selected' : '' ?> value="0">
                        Не вибрано
                    </option>
                    <?php foreach (Web\App\Model::findAll('users', 'archive = 0') as $user) { ?>
                        <option <?= htmlspecialchars($courier) == $user->id ? 'selected' : ''; ?>
                                value="<?= $user->id ?>">
                            <?= $user->name ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php }

    if ($key == 'coupon') { ?>
        <div class="form-group">
            <label for="coupon" class="col-md-4 control-label">Купон</label>
            <div class="col-md-5">
                <input id="coupon" name="coupon" class="form-control" value="<?= htmlspecialchars($coupon) ?>">
            </div>
        </div>

        <div class="form-group none">
            <label class="col-md-4 control-label" for="coupon_search"></label>
            <div class="col-md-5">
                <select id="coupon_search" class="form-control" multiple></select>
            </div>
        </div>
    <?php }

    if ($key == 'comment') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="comment">Коментар до замовлення</label>
            <div class="col-md-5">
                <textarea class="form-control" name="comment" id="comment"><?= htmlspecialchars($comment) ?></textarea>
            </div>
        </div>

    <?php }

    if ($key == 'city_delivery') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="city">Місто <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <input id="city" required name="city" class="form-control" value="<?= htmlspecialchars($city) ?>">
            </div>
        </div>

        <div class="form-group none" id="city_select_container">
            <label class="col-md-4 control-label" for="city_select"></label>
            <div class="col-md-5">
                <select id="city_select" class="form-control" multiple></select>
                <span class="btn btn-danger btn-xs hiden close_multiple" data-id="city_select_container">X</span>
            </div>
        </div>
    <?php }

    if ($key == 'street') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="street">Вулиця</label>
            <div class="col-md-5">
                <input id="street" name="street" class="form-control" value="<?= htmlspecialchars($street) ?>">
            </div>
        </div>

        <div class="form-group none" id="street_select_container">
            <label class="col-md-4 control-label" for="street_select"></label>
            <div class="col-md-5">
                <select id="street_select" class="form-control" multiple></select>
                <span class="btn btn-danger btn-xs hiden close_multiple" data-id="street_select_container">X</span>
            </div>
        </div>
    <?php }

    if ($key == 'comment_address') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="comment_address">Коментар до адреси</label>
            <div class="col-md-5">
                <textarea class="form-control" name="comment_address"
                          id="comment_address"><?= htmlspecialchars($comment_address); ?></textarea>
            </div>
        </div>
    <?php }

    if ($key == 'prepayment') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="prepayment">Предоплата</label>
            <div class="col-md-5">
                <input id="prepayment" name="prepayment" class="form-control"
                       value="<?= htmlspecialchars($prepayment) ?>">
            </div>
        </div>
    <?php }

    if ($key == 'warehouse') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="warehouse">Магазин</label>
            <div class="col-md-5">
                <select id="warehouse" name="warehouse" class="form-control">
                    <?php foreach (\Web\Model\OrderSettings::getAll('shops') as $item) { ?>
                        <option <?= htmlspecialchars($warehouse) == $item->id ? 'selected' : '' ?> value="<?= $item->id ?>">
                            <?= $item->name ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php }

    if ($key == 'delivery') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="delivery">Транспортна компанія</label>
            <div class="col-md-5">
                <select id="delivery" name="delivery" class="form-control">
                    <?php foreach (Web\App\Model::getAll('logistics') as $item) { ?>
                        <option <?= htmlspecialchars(htmlspecialchars($delivery)) == $item->id ? 'selected' : ''; ?>
                                value="<?= $item->id ?>">
                            <?= $item->name ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php }

    if ($key == 'city_new_post') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="city_input">Місто <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <div class="input-group">
                    <input class="form-control" id="city_input" value="<?= htmlspecialchars($city_name) ?>">
                    <span class="input-group-addon pointer clear" data-id="city_input">X</span>
                </div>
            </div>
        </div>

        <input type="hidden" id="city" name="city" class="form-control" value="<?= htmlspecialchars($city); ?>">

        <div class="form-group none" id="city_select_container">
            <label class="col-md-4 control-label" for="city_select"></label>
            <div class="col-md-5">
                <select id="city_select" class="form-control" multiple></select>
                <span class="btn btn-danger btn-xs hiden close_multiple" data-id="city_select_container">X</span>
            </div>
        </div>

        <div class="form-group">
            <label class="col-md-4 control-label" for="warehouse">Відділення <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <select <?= $warehouses['disabled'] ? 'disabled' : '' ?> id="warehouse" name="warehouse"
                                                                         class="form-control">
                    <?php foreach ($warehouses['data'] as $item) { ?>
                        <option <?= $item['Ref'] == htmlspecialchars($warehouse) ? 'selected' : '' ?>
                                value="<?= $item['Ref'] ?>">
                            <?= $item['Description'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-group none">
            <label class="col-md-4 control-label" for="warehouse_search"></label>
            <div class="col-md-5">
                <select id="warehouse_search" class="form-control" multiple></select>
            </div>
        </div>

    <?php }

    if ($key == 'city_warehouse') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="city">Місто <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <input class="form-control" name="city" id="city" value="<?= htmlspecialchars($city) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="col-md-4 control-label" for="warehouse">Відділення <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <input id="warehouse" name="warehouse" class="form-control" value="<?= htmlspecialchars($warehouse) ?>">
            </div>
        </div>
    <?php }

    if ($key == 'ttn') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="street">Номер ТТН</label>
            <div class="col-md-5">
                <input id="street" name="street" class="form-control" value="<?= htmlspecialchars($ttn) ?>">
            </div>
        </div>
    <?php }

    if ($key == 'payment_status') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="payment_status">Статус оплати</label>
            <div class="col-md-5">
                <select id="payment_status" class="form-control" name="payment_status">
                    <option <?= !htmlspecialchars($payment_status) ? 'selected' : '' ?> value="0">Не оплачено</option>
                    <option <?= htmlspecialchars($payment_status) ? 'selected' : '' ?> value="1">Оплачено</option>
                </select>
            </div>
        </div>
    <?php }

    if ($key == 'pay_delivery') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="pay_delivery">Доставку оплачує</label>
            <div class="col-md-5">
                <select id="pay_delivery" name="pay_delivery" class="form-control">
                    <option <?= htmlspecialchars($pay_delivery) == 'recipient' ? 'selected' : '' ?> value="recipient">
                        Отримувач
                    </option>
                    <option <?= htmlspecialchars($pay_delivery) == 'sender' ? 'selected' : '' ?> value="sender">
                        Відправник
                    </option>
                </select>
            </div>
        </div>

    <?php }

    if ($key == 'form_delivery') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="form_delivery">Форма оплати</label>
            <div class="col-md-5">
                <select id="form_delivery" name="form_delivery" class="form-control">
                    <option disabled <?= htmlspecialchars($form_delivery) == 'on_the_card' ? 'selected' : ''; ?>
                            value="on_the_card">
                        Безготівкова
                    </option>
                    <option <?= htmlspecialchars($form_delivery) == 'imposed' ? 'selected' : 'imposed'; ?>
                            value="imposed">
                        Готівкова
                    </option>
                </select>
            </div>
        </div>
    <?php }

    if ($key == 'site') { ?>
        <div class="form-group">
            <label class="col-md-4 control-label" for="site">Сайт <i class="text-danger">*</i></label>
            <div class="col-md-5">
                <select id="site" required name="site" class="form-control">
                    <option value=""></option>
                    <?php foreach (\Web\App\Model::getAll('sites') as $item) { ?>
                        <option <?= $site == $item->id ? 'selected' : '' ?> value="<?= $item->id ?>">
                            <?= $item->name ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    <?php }

}
