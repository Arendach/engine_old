<div class="row">
    <div class="col-md-offset-4 col-md-5">
        <ul class="nav nav-tabs nav-justified">
            <li class="active">
                <a href="#seo_uk" data-toggle="tab">
                    <img src="<?= asset('icons/uk.ico') ?>">
                </a>
            </li>
            <li>
                <a href="#seo_ru" data-toggle="tab">
                    <img src="<?= asset('icons/ru.ico') ?>">
                </a>
            </li>
        </ul>
    </div>
</div>

<br>
<form action="<?= uri('product') ?>" class="form-horizontal" data-type="ajax">
    <input type="hidden" name="action" value="update_seo">
    <input type="hidden" name="id" value="<?= $product->id ?>">

    <div class="tab-content">
        <?php foreach (['uk', 'ru'] as $item) { ?>
            <div class="tab-pane<?= $item == 'uk' ? ' active' : '' ?>" id="seo_<?= $item ?>">
                <div class="form-group">
                    <abbr title="Довжина тексту - 60-80 символів з урахуванням пробілів. Назва и артикул - перші, за ними тип товара (салют, феєрверк). Приклад: (ДМБ SU-32 - салют, феєрверк на 150 пострілів від ТМ Феєрверк купити в інтернет-магазине дешево" data-toggle="tooltip"><i class="fa fa-question-circle" aria-hidden="true"></i></abbr><label class="col-md-4 control-label">Заголовок (Title)</label>
                    <div class="col-md-5">
                        <input class="form-control" name="meta_title_<?= $item ?>"
                               value="<?= $product->{"meta_title_$item"} ?>">
                    </div>
                </div>

                <div class="form-group">
                    <abbr title="Назва, артикул, феєрверк, салют, салютна установка, піротехніка, торгова марка. Приклад: ДмБ, su-32, феєрверк, салют, салютна установка, ТМ фейерверк" data-toggle="tooltip"><i class="fa fa-question-circle" aria-hidden="true"></i></abbr><label class="col-md-4 control-label">Ключові слова (Keywords)</label>
                    <div class="col-md-5">
                        <input class="form-control" name="meta_keywords_<?= $item ?>"
                               value="<?= $product->{"meta_keywords_$item"} ?>">
                    </div>
                </div>

                <div class="form-group">
                    <abbr title="180-300 символів з урахуванням пробілів. Коротко по характеристикам, трохи про товар, умови доставки, де і як купити. Приклад: Феєрверк ДМБ SU-32 - Салютна установка 150 пострілів, калібр 20-30мм. ПРАЦЮЄ 90 сек. Купити в интернет-магазине в Києві, цена від 1900 грн. БЕЗКОШТОВНА ДОСТАВКА. Працюємо цілодобово - доставляємо по Україні" data-toggle="tooltip"><i class="fa fa-question-circle" aria-hidden="true"></i></abbr><label class="col-md-4 control-label">Опис сторінки (Description)</label>
                    <div class="col-md-5">
                        <input class="form-control" name="meta_description_<?= $item ?>"
                               value="<?= $product->{"meta_description_$item"} ?>">
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="form-group">
        <div class="col-md-offset-4 col-md-5">
            <button class="btn btn-sm btn-primary">Зберегти</button>
        </div>
    </div>
</form>