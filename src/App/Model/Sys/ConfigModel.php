<?php

namespace Model;
// hook model_config_use.php

use App\Model;

class ConfigModel extends Model
{
	// hook model_config_public_start.php
	public $table = 'zx_config';
	public $config = [];
	public $index = 'name';

	// hook model_config_public_end.php


	// hook model_config_start.php
	public function GetALL($new = 0)
	{
		// hook model_config_getall_start.php
		if ($new == 1 || empty($this->config)) {
			$data = $this->select();
			foreach ($data as $k => $v) {
				if ($v['is_json'] == 1) {
					$data[$k]['value'] = xn_json_decode($v['value']);
				}
			}
			$this->config = arrlist_key_values($data, 'name', 'value');
		}
		// hook model_config_getall_end.php
		return $this->config;
	}

	public function SetValue($name, $value)
	{
		// hook model_config_setvalue_start.php
		$this->update(['name' => $name], ['value' => $value]);
		// hook model_config_setvalue_end.php
	}

	public function form_fmt($data)
	{
		// hook model_config_form_fmt_start.php
		$str = '<form class="layui-form" action="../config">';
		foreach ($data as $k => $v) {
			// hook model_config_form_fmt_switch_start.php
			switch ($v['type']) {
				case 'text':
					$str .= $this->input_fmt($v);
					break;
				case 'textarea':
					$str .= $this->textarea_fmt($v);
					break;
				case 'radio':
					$str .= $this->radio_fmt($v);
					break;
				case 'select':
					$str .= $this->select_fmt($v);
					break;
				case 'checkbox':
					$str .= $this->checkbox_fmt($v);
					break;
				default:
					// hook model_config_form_fmt_switch_default.php
			}
			// hook model_config_form_fmt_switch_end.php
		}

		$str .= '<div class="layui-form-item">
                    <div class="layui-input-block">
                        <button type="button" class="layui-btn" lay-submit lay-filter="*">立即提交</button>
                        <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                    </div>
                </div>';
		$str .= '</form>';
		// hook model_config_form_fmt_end.php
		return $str;
	}

	public function input_fmt($data)
	{
		// hook model_config_input_fmt_start.php
		return '<div class="layui-form-item"><label class="layui-form-label">' . $data['title'] . '</label><div class="layui-input-block"><input placeholder="' . $data['placeholder'] . '" class="layui-input" type=' . $data['type'] . ' name="' . $data['name'] . '" value="' . $data['value'] . '"></div></div>';
	}

	public function radio_fmt($data)
	{
		// hook model_config_radio_fmt_start.php
		$op = xn_json_decode($data['options']);
		if (empty($op) || $op == NULL) {
			return '';
		}
		$str = '<div class="layui-form-item"><label class="layui-form-label">' . $data['title'] . '</label><div class="layui-input-block">';
		foreach ($op as $k => $v) {
			$str .= '<input ' . ($data['value'] == $k ? 'checked' : '') . ' title="' . $v . '" type="radio" name="' . $data['name'] . '" value="' . $k . '">';
		}
		return $str . "</div></div>";
	}

	public function select_fmt($data)
	{
		// hook model_config_select_fmt_start.php
		$op = xn_json_decode($data['options']);
		if (empty($op) || $op == NULL) {
			return '';
		}
		$str = '<div class="layui-form-item"><label class="layui-form-label">' . $data['title'] . '</label><div class="layui-input-block">';
		$str = '<select class="layui-input" name="' . $data['name'] . '" placeholder="' . $data['placeholder'] . '" autocomplete="off"><option value="">请选择</option>';
		foreach ($op as $k => $v) {
			$str .= '<option value="' . $k . '">' . $v . '</option>';
		}
		return $str . "</select></div></div>";
	}

	public function checkbox_fmt($data)
	{
		// hook model_config_checkbox_fmt_start.php
		$op = xn_json_decode($data['options']);
		if (empty($op) || $op == NULL) {
			return '';
		}
		$str = '<div class="layui-form-item"><label class="layui-form-label">' . $data['title'] . '</label><div class="layui-input-block">';
		foreach ($op as $k => $v) {
			$str .= '<input ' . ($data['value'] == $k ? 'checked' : '') . ' title="' . $v . '" type="checkbox" name="' . $data['name'] . '" value="' . $data['value'] . '">';
		}
		return $str . "</div></div>";
	}

	public function textarea_fmt($data)
	{
		// hook model_config_textarea_fmt_start.php
		return '<div class="layui-form-item"><label class="layui-form-label">' . $data['title'] . '</label><div class="layui-input-block"><textarea placeholder="' . $data['placeholder'] . '" class="layui-textarea" name="' . $data['name'] . '" >' . $data['value'] . '</textarea></div></div>';
	}

	// hook model_config_end.php
}

?>