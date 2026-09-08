/**
 * YGB Avisos - Admin Separator Script
 * 
 * @package YGB_Avisos
 * @since 1.2.1
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // ? Los s¨ªmbolos ahora se obtienen desde PHP via wp_localize_script
        // Ver en ygb-avisos.php la funci¨®n enqueue_admin_assets()
        
        /**
         * Actualiza la vista previa del separador
         * @param {string} valor - Texto del separador
         * @param {string} animacion - Tipo de animaci¨®n
         */
        function actualizarVistaPrevia(valor, animacion) {
            var $preview = $('.separator-preview');
            // ? Usamos text() en lugar de html() para prevenir XSS
            $preview.text(valor);
            
            $preview.removeClass('anim-pulse anim-spin anim-bounce anim-flash anim-shake');
            
            if (animacion === 'pulse') {
                $preview.addClass('anim-pulse');
            } else if (animacion === 'spin') {
                $preview.addClass('anim-spin');
            } else if (animacion === 'bounce') {
                $preview.addClass('anim-bounce');
            } else if (animacion === 'flash') {
                $preview.addClass('anim-flash');
            } else if (animacion === 'shake') {
                $preview.addClass('anim-shake');
            }
        }
        
        /**
         * Obtiene el valor del separador seg¨²n el tipo seleccionado
         * @returns {string}
         */
        function obtenerValorSeparador() {
            var tipo = $('#ygb_separador_tipo').val();
            var valor = '';
            
            if (tipo === 'simbolo') {
                var key = $('#ygb_separador_simbolo').val();
                // ? Usamos los datos desde PHP (inyectados via wp_localize_script)
                if (window.ygbAvisosAdmin && window.ygbAvisosAdmin.simbolos) {
                    valor = window.ygbAvisosAdmin.simbolos[key] || '??????';
                } else {
                    // Fallback seguro
                    valor = $('#ygb_separador_simbolo option:selected').text() || '??????';
                }
            } else if (tipo === 'texto') {
                var texto = $('#ygb_separador_texto').val();
                valor = texto + ' ' + texto + ' ' + texto;
            } else if (tipo === 'linea') {
                valor = $('#ygb_separador_linea').val();
            } else if (tipo === 'flecha') {
                valor = $('#ygb_separador_flecha').val();
            } else if (tipo === 'emoji') {
                valor = $('#ygb_separador_emoji').val();
            }
            
            return valor || '??????';
        }
        
        /**
         * Actualiza el separador seg¨²n la configuraci¨®n
         */
        function actualizarSeparador() {
            var tipo = $('#ygb_separador_tipo').val();
            
            // Ocultar todos los contenedores
            $('#ygb_separador_simbolo_container, #ygb_separador_texto_container, #ygb_separador_linea_container, #ygb_separador_flecha_container, #ygb_separador_emoji_container').hide();
            
            // Mostrar el contenedor correspondiente
            if (tipo === 'simbolo') {
                $('#ygb_separador_simbolo_container').show();
            } else if (tipo === 'texto') {
                $('#ygb_separador_texto_container').show();
            } else if (tipo === 'linea') {
                $('#ygb_separador_linea_container').show();
            } else if (tipo === 'flecha') {
                $('#ygb_separador_flecha_container').show();
            } else if (tipo === 'emoji') {
                $('#ygb_separador_emoji_container').show();
            }
            
            var valor = obtenerValorSeparador();
            var animacion = $('#ygb_separador_animacion').val();
            
            // Actualizar campo oculto
            $('#ygb_separador_valor_final').val(valor);
            
            // Actualizar preview
            actualizarVistaPrevia(valor, animacion);
        }
        
        // Event listeners
        $('#ygb_separador_tipo').on('change', actualizarSeparador);
        $('#ygb_separador_simbolo').on('change', actualizarSeparador);
        $('#ygb_separador_texto').on('input', actualizarSeparador);
        $('#ygb_separador_linea').on('change', actualizarSeparador);
        $('#ygb_separador_flecha').on('change', actualizarSeparador);
        $('#ygb_separador_emoji').on('change', actualizarSeparador);
        $('#ygb_separador_animacion').on('change', function() {
            var valor = $('#ygb_separador_valor_final').val();
            var animacion = $(this).val();
            actualizarVistaPrevia(valor, animacion);
        });
        
        // Inicializar
        actualizarSeparador();
    });
    
})(jQuery);