<?php
/**
 * Frontend output.
 *
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$record = ! empty( $data['record'] ) ? $data['record'] : null;
$can_render_form = $record && 'paid' === $record['payment_status'] && empty( $record['submitted_at'] );
?>
<div class="pv-widget">
	<?php if ( $data['submitted'] ) : ?>
		<div class="pv-message pv-message-success"><?php echo esc_html( $data['messages']['success'] ); ?></div>
	<?php endif; ?>
	<?php if ( ! empty( $data['request_error'] ) ) : ?>
		<div class="pv-message pv-error-box"><?php echo esc_html( $data['request_error'] ); ?></div>
	<?php endif; ?>

	<?php if ( empty( $record ) ) : ?>
		<?php include PV_PLUGIN_DIR . 'templates/frontend-button.php'; ?>
	<?php elseif ( 'paid' !== $record['payment_status'] ) : ?>
		<div class="pv-status-box" data-pv-status data-reference="<?php echo esc_attr( $data['access']['reference'] ); ?>" data-token="<?php echo esc_attr( $data['access']['token'] ); ?>" data-url="<?php echo esc_url( $data['poll_url'] ); ?>">
			<p class="pv-message"><?php echo esc_html( $data['messages']['waiting'] ); ?></p>
			<p class="pv-timeout-message" hidden><?php echo esc_html( $data['messages']['timeout'] ); ?></p>
		</div>
	<?php elseif ( ! empty( $record['submitted_at'] ) ) : ?>
		<div class="pv-message pv-message-success"><?php echo esc_html( $data['messages']['success'] ); ?></div>
	<?php endif; ?>

	<?php if ( $can_render_form ) : ?>
		<form method="post" action="<?php echo esc_url( $data['submit_action'] ); ?>" class="pv-questionnaire-form">
			<input type="hidden" name="action" value="pv_submit_questionnaire" />
			<input type="hidden" name="pv_nonce" value="<?php echo esc_attr( $data['submit_nonce'] ); ?>" />
			<input type="hidden" name="reference" value="<?php echo esc_attr( $data['access']['reference'] ); ?>" />
			<input type="hidden" name="token" value="<?php echo esc_attr( $data['access']['token'] ); ?>" />

			<?php foreach ( $data['questions'] as $question ) : ?>
				<?php
				$key = $question['question_key'];
				$old = isset( $data['form_old'][ $key ] ) ? $data['form_old'][ $key ] : '';
				$error = isset( $data['form_errors'][ $key ] ) ? $data['form_errors'][ $key ] : '';
				?>
				<div class="pv-field">
					<label for="<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $question['label_text'] ); ?>
						<?php if ( $question['is_required'] ) : ?><span class="pv-required">*</span><?php endif; ?>
					</label>
					<?php if ( ! empty( $question['help_text'] ) ) : ?>
						<p class="pv-help"><?php echo esc_html( $question['help_text'] ); ?></p>
					<?php endif; ?>
					<?php switch ( $question['field_type'] ) :
						case 'textarea': ?>
							<textarea id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="4"><?php echo esc_textarea( $old ); ?></textarea>
							<?php break; ?>
						<?php case 'select': ?>
							<select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>">
								<option value=""><?php echo esc_html__( 'Изберете...', 'platen-vaprosnik' ); ?></option>
								<?php foreach ( $question['options'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $old, $option ); ?>><?php echo esc_html( $option ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php break; ?>
						<?php case 'radio': ?>
							<div class="pv-options">
								<?php foreach ( $question['options'] as $option ) : ?>
									<label><input type="radio" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $option ); ?>" <?php checked( $old, $option ); ?> /> <?php echo esc_html( $option ); ?></label>
								<?php endforeach; ?>
							</div>
							<?php break; ?>
						<?php case 'checkbox': ?>
							<div class="pv-options">
								<?php $old_values = is_array( $old ) ? $old : array(); ?>
								<?php foreach ( $question['options'] as $option ) : ?>
									<label><input type="checkbox" name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $option ); ?>" <?php checked( in_array( $option, $old_values, true ) ); ?> /> <?php echo esc_html( $option ); ?></label>
								<?php endforeach; ?>
							</div>
							<?php break; ?>
						<?php default : ?>
							<?php $input_type = 'phone' === $question['field_type'] ? 'tel' : $question['field_type']; ?>
							<input id="<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $input_type ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $old ); ?>" />
					<?php endswitch; ?>
					<?php if ( $error ) : ?><p class="pv-error"><?php echo esc_html( $error ); ?></p><?php endif; ?>
				</div>
			<?php endforeach; ?>

			<div class="pv-field">
				<label for="pv_name"><?php echo esc_html__( 'Име и фамилия', 'platen-vaprosnik' ); ?> <span class="pv-required">*</span></label>
				<input id="pv_name" type="text" name="pv_name" value="<?php echo isset( $data['form_old']['name'] ) ? esc_attr( $data['form_old']['name'] ) : ''; ?>" />
				<?php if ( ! empty( $data['form_errors']['pv_name'] ) ) : ?><p class="pv-error"><?php echo esc_html( $data['form_errors']['pv_name'] ); ?></p><?php endif; ?>
			</div>
			<div class="pv-field">
				<label for="pv_email"><?php echo esc_html__( 'Имейл', 'platen-vaprosnik' ); ?> <span class="pv-required">*</span></label>
				<input id="pv_email" type="email" name="pv_email" value="<?php echo isset( $data['form_old']['email'] ) ? esc_attr( $data['form_old']['email'] ) : ''; ?>" />
				<?php if ( ! empty( $data['form_errors']['pv_email'] ) ) : ?><p class="pv-error"><?php echo esc_html( $data['form_errors']['pv_email'] ); ?></p><?php endif; ?>
			</div>
			<div class="pv-field">
				<label for="pv_phone"><?php echo esc_html__( 'Телефон', 'platen-vaprosnik' ); ?> <span class="pv-required">*</span></label>
				<input id="pv_phone" type="text" name="pv_phone" value="<?php echo isset( $data['form_old']['phone'] ) ? esc_attr( $data['form_old']['phone'] ) : ''; ?>" />
				<?php if ( ! empty( $data['form_errors']['pv_phone'] ) ) : ?><p class="pv-error"><?php echo esc_html( $data['form_errors']['pv_phone'] ); ?></p><?php endif; ?>
			</div>

			<button type="submit" class="pv-button"><?php echo esc_html__( 'Изпрати въпросника', 'platen-vaprosnik' ); ?></button>
		</form>
	<?php endif; ?>
</div>
