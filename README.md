# Платен въпросник

WordPress plugin за платен достъп до въпросник чрез Stripe Payment Link и сигурно потвърждение през Stripe webhook.

## Какво включва
- shortcode `[platen_vaprosnik]`
- Gutenberg блок „Платен въпросник"
- Stripe Payment Link поток с `client_reference_id`
- webhook потвърждение като единствен източник на истина
- административен интерфейс на български език
- динамичен редактор на въпроси
- списък с плащания и отговори
- custom database tables за плащания, въпроси и изпратени отговори

## Инсталация
1. Качете папката на плъгина в `wp-content/plugins/platen-vaprosnik`.
2. Активирайте плъгина от WordPress администрацията.
3. Отворете **Платен въпросник → Настройки**.
4. Попълнете:
   - **URL на Stripe Payment Link**
   - **Stripe webhook secret**
   - **Страница за въпросник**
   - **Текст на бутона**
   - **Режим Тестов / Реален**
   - **Съобщение след успешно изпращане**
5. Копирайте webhook URL-а от настройките и го добавете в Stripe.
6. В Stripe Payment Link настройте redirect след успешно плащане към избраната WordPress страница за въпросника.
7. Поставете shortcode-а `[platen_vaprosnik]` или Gutenberg блока на желаната страница.
8. Отворете **Платен въпросник → Въпроси** и създайте въпросите.

## Примерни съобщения на български
- „Стартирай“
- „Плащането се потвърждава. Моля, изчакайте...“
- „Все още не можем да потвърдим плащането. Моля, опитайте отново след малко.“
- „Webhook-ът е конфигуриран“
- „Настройките са запазени успешно“

## Бележки за Stripe
- Плъгинът не разчита само на връщането от Stripe.
- Достъпът до въпросника се дава единствено след `checkout.session.completed`, потвърден чрез валиден webhook подпис.
- Stripe тайните никога не се показват на фронтенда.

## База данни
При активация се създават таблиците:
- `wp_pv_payments`
- `wp_pv_questions`
- `wp_pv_submissions`

## Разработка
Основни файлове:
- `platen-vaprosnik.php`
- `includes/class-plugin.php`
- `includes/class-admin.php`
- `includes/class-settings.php`
- `includes/class-payment-link.php`
- `includes/class-webhook-handler.php`
- `includes/class-questionnaire.php`
- `includes/class-submissions-repository.php`
- `includes/class-activator.php`
