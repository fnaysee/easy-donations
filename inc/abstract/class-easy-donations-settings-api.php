<?php

class Easy_Donations_Settings_API {
    
    const version = '1.0';
    
    /**
     * Generate a field for any form
     * @param mixed $args 
     * @return mixed
     */
    public function get_a_form_field( $arguments ) {
        
        $args = $arguments;
        if( ! isset( $args['type'] ) )
            $args["type"] = 'text';
        

        return $this->get_labeled_field_html( $args );
    }
    
    public function get_labeled_field_html( $args ) {
        $output = '<div>';

        if( isset( $args['id'] ) && isset( $args['title'] ) )
            $output .= $this->get_label( $args['id'], $args['title'] );
        
        $output .= $this->get_field( $args );

        if( isset( $args['description'] ) )
            $output = $this->get_description_html( $args['description'] );
        
        return $output . '</div>';
    }
    
    public function get_field( $args ) {
        $type = (isset( $args['type'] )) ? $args['type'] : 'text';
        
        if( method_exists( $this, 'get_' . $type . '_field' ) ) {
            return $this->{'get_' . $type . '_field'}( $args );
        }
        else
            return $this->get_text_field( $args );
    }
    
    public function get_label( $for, $title ) {
        return "<label class='edt-title' for='{$for}' >{$title}</label>";
    }
    
    public function get_description_html( $desc ) {
        return "<pre>{$desc}</pre>";
    }
    
    public function get_text_field( $args ) {
        $id = ( isset( $args['id'] ) ) ? "id='{$args['id']}'" : ''; 
        $value   = ( isset( $args['value'] ) ) ? $args['value'] : '';
        $classes = ( isset( $args['classes'] ) ) ? $args['classes'] : '';
        $text = ( isset( $args['text'] ) ) ? $args['text'] : '';
        
        return "<input {$id} type='text' name='{$args['name']}' class='{$classes}' value='{$value}' />{$text}";
    }
    
    public function get_radio_field( $args ) {
        $id = ( isset( $args['id'] ) ) ? "id='{$args['id']}'" : ''; 
        $value   = ( isset( $args['value'] ) ) ? $args['value'] : '';
        $classes = ( isset( $args['classes'] ) ) ? $args['classes'] : '';
        $text = ( isset( $args['text'] ) ) ? $args['text'] : '';
        $checked = ( isset( $args['checked'] ) && $args['checked'] ) ? 'checked="checked"' : "";
        
        return "<input {$id} type='radio' name='{$args['name']}' class='{$classes}' value='{$value}' {$checked} />{$text}";  
    }
    
    public function get_checkbox_field( $args ) {
        $id = ( isset( $args['id'] ) ) ? "id='{$args['id']}'" : ''; 
        $value   = ( isset( $args['value'] ) ) ? $args['value'] : '';
        $classes = ( isset( $args['classes'] ) ) ? $args['classes'] : '';
        $text = ( isset( $args['text'] ) ) ? $args['text'] : '';
        $is_checked = ( isset( $args['checked'] ) && $args['checked'] == true ) ? 'checked="checked"' : '';

        return "<input {$id} type='checkbox' name='{$args['name']}' class='{$classes}' value='{$value}' {$is_checked} />{$text}";
    }
    
    public function get_select_field( $args ) {
    
    }
    
    public function get_textarea_field( $args ) {
        $id = ( isset( $args['id'] ) ) ? "id='{$args['id']}'" : '';
        $content = ( isset( $args['content'] ) ) ? $args['content'] : '';
        $classes = ( isset( $args['classes'] ) ) ? $args['classes'] : '';
        $text = ( isset( $args['text'] ) ) ? $args['text'] : '';
        
        return "<textarea {$id} name='{$args['name']}' class='{$classes}' >{$content}</textarea>{$text}";
    }
    
    public function get_hidden_field( $args ) {
        $id = ( isset( $args['id'] ) ) ? "id='{$args['id']}'" : ''; 
        $value   = ( isset( $args['value'] ) ) ? $args['value'] : '';
        $classes = ( isset( $args['classes'] ) ) ? $args['classes'] : '';

        return "<input {$id} type='hidden' name='{$args['name']}' class='{$classes}' value='{$value}' />";
    }
    
    public function get_password_field( $args ) {
        $id = ( isset( $args['id'] ) ) ? "id='{$args['id']}'" : ''; 
        $value   = ( isset( $args['value'] ) ) ? $args['value'] : '';
        $classes = ( isset( $args['classes'] ) ) ? $args['classes'] : '';
        $text = ( isset( $args['text'] ) ) ? $args['text'] : '';
        
        return "<input {$id} type='password' name='{$args['name']}' class='{$classes}' value='{$value}' />{$text}";
    }
}