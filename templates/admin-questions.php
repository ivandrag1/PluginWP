<?php
/**
 * Questions template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options_text = '';
if ( ! empty( $question['options_json'] ) ) {
	$options = json_decode( $question['options_json'], true );
	if ( is_array( $options ) ) {
		$options_text = implode( PHP_EOL, $options );
	}
}
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Въпроси', 'platen-vaprosnik' ); ?></h1>
	<?php if ( isset( $_GET['message'] ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html__( 'Промените са запазени успешно.', 'platen-vaprosnik' ); ?></p></div>
	<?php endif; ?>

	<div class="pv-admin-grid">
		<div class="pv-admin-card">
			<h2><?php echo $question ? esc_html__( 'Редактирай въпрос', 'platen-vaprosnik' ) : esc_html__( 'Добави въпрос', 'platen-vaprosnik' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'pv_save_question' ); ?>
				<input type="hidden" name="pv_question_action" value="save" />
				<input type="hidden" name="id" value="<?php echo ! empty( $question['id'] ) ? esc_attr( $question['id'] ) : 0; ?>" />
				<table class="form-table">
					<tr>
						<th><label for="question_key"><?php echo esc_html__( 'Ключ на въпроса', 'platen-vaprosnik' ); ?></label></th>
						<td><input required type="text" id="question_key" name="question_key" value="<?php echo ! empty( $question['question_key'] ) ? esc_attr( $question['question_key'] ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th><label for="label_text"><?php echo esc_html__( 'Етикет', 'platen-vaprosnik' ); ?></label></th>
						<td><input required type="text" class="regular-text" id="label_text" name="label_text" value="<?php echo ! empty( $question['label_text'] ) ? esc_attr( $question['label_text'] ) : ''; ?>" /></td>
					</tr>
					<tr>
						<th><label for="field_type"><?php echo esc_html__( 'Тип на полето', 'platen-vaprosnik' ); ?></label></th>
						<td>
							<select id="field_type" name="field_type">
								<?php foreach ( array( 'text', 'textarea', 'email', 'phone', 'select', 'radio', 'checkbox' ) as $type ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>" <?php selected( ! empty( $question['field_type'] ) ? $question['field_type'] : 'text', $type ); ?>><?php echo esc_html( $type ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="help_text"><?php echo esc_html__( 'Помощен текст', 'platen-vaprosnik' ); ?></label></th>
						<td><textarea id="help_text" class="large-text" rows="3" name="help_text"><?php echo ! empty( $question['help_text'] ) ? esc_textarea( $question['help_text'] ) : ''; ?></textarea></td>
					</tr>
					<tr>
						<th><label for="options"><?php echo esc_html__( 'Опции', 'platen-vaprosnik' ); ?></label></th>
						<td>
							<textarea id="options" class="large-text" rows="6" name="options"><?php echo esc_textarea( $options_text ); ?></textarea>
							<p class="description"><?php echo esc_html__( 'По една опция на ред за select / radio / checkbox.', 'platen-vaprosnik' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="display_order"><?php echo esc_html__( 'Подредба', 'platen-vaprosnik' ); ?></label></th>
						<td><input type="number" id="display_order" name="display_order" value="<?php echo ! empty( $question['display_order'] ) ? esc_attr( $question['display_order'] ) : 0; ?>" /></td>
					</tr>
					<tr>
						<th><?php echo esc_html__( 'Задължителен въпрос', 'platen-vaprosnik' ); ?></th>
						<td><label><input type="checkbox" name="is_required" value="1" <?php checked( ! empty( $question['is_required'] ) ); ?> /> <?php echo esc_html__( 'Маркирай като задължителен', 'platen-vaprosnik' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button( $question ? __( 'Запази промените', 'platen-vaprosnik' ) : __( 'Добави въпрос', 'platen-vaprosnik' ) ); ?>
			</form>
		</div>

		<div class="pv-admin-card">
			<h2><?php echo esc_html__( 'Подреди въпросите', 'platen-vaprosnik' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'pv_reorder_questions' ); ?>
				<input type="hidden" name="pv_question_action" value="reorder" />
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Етикет', 'platen-vaprosnik' ); ?></th>
							<th><?php echo esc_html__( 'Тип на полето', 'platen-vaprosnik' ); ?></th>
							<th><?php echo esc_html__( 'Подредба', 'platen-vaprosnik' ); ?></th>
							<th><?php echo esc_html__( 'Действия', 'platen-vaprosnik' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $questions ) ) : ?>
							<tr><td colspan="4"><?php echo esc_html__( 'Все още няма въпроси.', 'platen-vaprosnik' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $questions as $item ) : ?>
								<tr>
									<td><?php echo esc_html( $item['label_text'] ); ?><?php if ( ! empty( $item['is_required'] ) ) : ?> <strong>*</strong><?php endif; ?></td>
									<td><?php echo esc_html( $item['field_type'] ); ?></td>
									<td><input type="number" name="display_order[<?php echo esc_attr( $item['id'] ); ?>]" value="<?php echo esc_attr( $item['display_order'] ); ?>" /></td>
									<td>
										<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=platen-vaprosnik-questions&edit=' . absint( $item['id'] ) ) ); ?>"><?php echo esc_html__( 'Редактирай', 'platen-vaprosnik' ); ?></a>
										<a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=platen-vaprosnik-questions&pv_delete_question=' . absint( $item['id'] ) ), 'pv_delete_question_' . absint( $item['id'] ) ) ); ?>"><?php echo esc_html__( 'Изтрий', 'platen-vaprosnik' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				<?php submit_button( __( 'Запази подредбата', 'platen-vaprosnik' ) ); ?>
			</form>
		</div>
	</div>
</div>
