/**
 * YGB Avisos - Ticker Scripts
 * 
 * @package YGB_Avisos
 * @since 1.2.1
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // ? Manejador unificado para botones de cerrar (sin inline onclick)
        $(document).on('click', '.ygb-ticker-close-btn, .ygb-ticker-close', function(e) {
            e.preventDefault();
            var $target = $(this).closest('.ygb-ticker, .ygb-universal-ticker, [id^="ygb-universal-"]');
            
            // Si hay un data-target espec¨ªfico
            var targetId = $(this).data('target');
            if (targetId) {
                $target = $('#' + targetId);
            }
            
            $target.slideUp(300, function() {
                $(this).remove();
            });
        });
        
        $('.ygb-ticker').each(function() {
            var $ticker = $(this);
            var speed = parseInt($ticker.data('speed')) || 40;
            var $track = $ticker.find('.ygb-ticker-track');
            var $firstContent = $track.find('.ygb-ticker-track-content').first();
            var $separator = $track.find('.ygb-ticker-separator-cycle');
            
            // Verificar que tenemos la estructura correcta
            if ($track.find('.ygb-ticker-track-content').length < 2) {
                var html = $firstContent.html();
                var separatorHtml = $separator.length ? $separator.first()[0].outerHTML : '<span class="ygb-ticker-separator-cycle">??????</span>';
                
                // Reconstruir el track si falta algo
                $track.empty();
                $track.append('<div class="ygb-ticker-track-content">' + html + '</div>');
                $track.append(separatorHtml);
                $track.append('<div class="ygb-ticker-track-content">' + html + '</div>');
                
                // Reasignar variables
                $firstContent = $track.find('.ygb-ticker-track-content').first();
                $separator = $track.find('.ygb-ticker-separator-cycle');
            }
            
            // Calcular ancho de UN CICLO COMPLETO
            var contentWidth = $firstContent[0].scrollWidth;
            
            // Ancho del separador incluyendo m¨¢rgenes
            var $sepClone = $separator.clone().css({
                'position': 'absolute',
                'visibility': 'hidden',
                'display': 'inline-block',
                'margin': '0 30px'
            }).appendTo('body');
            var separatorWidth = $sepClone.outerWidth(true);
            $sepClone.remove();
            
            // Si no se pudo calcular, usar valores por defecto
            if (separatorWidth === 0) {
                separatorWidth = 70;
            }
            
            // Ancho total de un ciclo
            var cycleWidth = contentWidth + separatorWidth;
            
            // La animaci¨®n debe mover exactamente UN ciclo
            var duration = cycleWidth / speed;
            
            // Ancho total del track (2 ciclos completos)
            var totalWidth = cycleWidth * 2;
            
            // Aplicar los estilos calculados
            $track.css({
                'animation-duration': duration + 's',
                'width': totalWidth + 'px'
            });
            
            // Pausar en hover
            var pauseHover = $ticker.data('pause-hover');
            if (pauseHover === 'si' || pauseHover === undefined) {
                $ticker.on('mouseenter', function() {
                    $track.css('animation-play-state', 'paused');
                }).on('mouseleave', function() {
                    $track.css('animation-play-state', 'running');
                });
            }
        });
        
        // Ajustar posici¨®n si hay m¨²ltiples tickers fijos
        var fixedTickers = $('.ygb-ticker[style*="position: fixed"], .ygb-universal-ticker');
        if (fixedTickers.length > 1) {
            var topOffset = 0;
            fixedTickers.each(function() {
                if ($(this).css('top') === '0px') {
                    $(this).css('top', topOffset + 'px');
                    topOffset += $(this).outerHeight();
                }
            });
        }
    });
    
})(jQuery);