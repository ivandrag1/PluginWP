<?php
/**
 * Submissions template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Плащания и отговори', 'platen-vaprosnik' ); ?></h1>
	<?php if ( ! empty( $detail ) ) : ?>
		<?php $payment = $detail['payment']; ?>
		<?php $submission = $detail['submission']; ?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=platen-vaprosnik-submissions' ) ); ?>">&larr; <?php echo esc_html__( 'Назад към списъка', 'platen-vaprosnik' ); ?></a></p>
		<div class="pv-admin-grid">
			<div class="pv-admin-card">
				<h2><?php echo esc_html__( 'Пълна информация за плащането', 'platen-vaprosnik' ); ?></h2>
				<table class="widefat striped">
					<tbody>
						<tr><th><?php echo esc_html__( 'ID', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( $payment['id'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Статус на плащане', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( $payment['payment_status'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Платена сума', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( $payment['amount_total'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Валута', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( strtoupper( (string) $payment['currency'] ) ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Stripe session ID', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( $payment['stripe_session_id'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Stripe event ID', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( $payment['stripe_event_id'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Stripe reference IDs', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( $payment['client_reference_id'] ); ?><br /><?php echo esc_html( $payment['internal_reference_id'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Имейл на клиента', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( $payment['customer_email'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Дата на плащане', 'platen-vaprosnik' ); ?></th><td><?php echo esc_html( $payment['paid_at'] ); ?></td></tr>
					</tbody>
				</table>
			</div>
			<div class="pv-admin-card">
				<h2><?php echo esc_html__( 'Всички отговори на въпросника', 'platen-vaprosnik' ); ?></h2>
				<?php if ( ! empty( $submission ) ) : ?>
					<p><strong><?php echo esc_html__( 'Име', 'platen-vaprosnik' ); ?>:</strong> <?php echo esc_html( $submission['name'] ); ?></p>
					<p><strong><?php echo esc_html__( 'Имейл', 'platen-vaprosnik' ); ?>:</strong> <?php echo esc_html( $submission['email'] ); ?></p>
					<p><strong><?php echo esc_html__( 'Телефон', 'platen-vaprosnik' ); ?>:</strong> <?php echo esc_html( $submission['phone'] ); ?></p>
					<?php $answers = json_decode( $submission['answers_json'], true ); ?>
					<?php if ( ! empty( $answers ) ) : ?>
						<table class="widefat striped">
							<thead><tr><th><?php echo esc_html__( 'Въпрос', 'platen-vaprosnik' ); ?></th><th><?php echo esc_html__( 'Отговор', 'platen-vaprosnik' ); ?></th></tr></thead>
							<tbody>
							<?php foreach ( $answers as $key => $answer ) : ?>
								<tr><td><?php echo esc_html( $key ); ?></td><td><?php echo esc_html( is_array( $answer ) ? implode( ', ', $answer ) : $answer ); ?></td></tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				<?php else : ?>
					<p><?php echo esc_html__( 'Все още няма изпратен въпросник.', 'platen-vaprosnik' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="pv-admin-card">
				<h2><?php echo esc_html__( 'История на статуса', 'platen-vaprosnik' ); ?></h2>
				<?php $history = json_decode( $payment['status_history'], true ); ?>
				<?php if ( ! empty( $history ) ) : ?>
					<ul class="pv-history-list">
						<?php foreach ( $history as $entry ) : ?>
							<li><strong><?php echo esc_html( $entry['status'] ); ?></strong> — <?php echo esc_html( $entry['message'] ); ?> <em>(<?php echo esc_html( $entry['timestamp'] ); ?>)</em></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'ID', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Статус на плащане', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Платена сума', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Валута', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Име', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Имейл', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Телефон', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Дата на плащане', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Дата на изпращане', 'platen-vaprosnik' ); ?></th>
					<th><?php echo esc_html__( 'Действия', 'platen-vaprosnik' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="10"><?php echo esc_html__( 'Все още няма записи.', 'platen-vaprosnik' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $items as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item['id'] ); ?></td>
							<td><?php echo esc_html( $item['payment_status'] ); ?></td>
							<td><?php echo esc_html( $item['amount_total'] ); ?></td>
							<td><?php echo esc_html( strtoupper( (string) $item['currency'] ) ); ?></td>
							<td><?php echo esc_html( $item['name'] ); ?></td>
							<td><?php echo esc_html( $item['email'] ); ?></td>
							<td><?php echo esc_html( $item['phone'] ); ?></td>
							<td><?php echo esc_html( $item['paid_at'] ); ?></td>
							<td><?php echo esc_html( $item['submitted_at'] ); ?></td>
							<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=platen-vaprosnik-submissions&view=' . absint( $item['id'] ) ) ); ?>"><?php echo esc_html__( 'Преглед', 'platen-vaprosnik' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
