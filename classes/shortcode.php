<?php
class Meow_JPNG_Shortcode {
  /** Meow_JPNG_Core instance */
  public $core;

  /** Indicates whether the hover effect is enabled */
  private $hoverOnly;

  public function __construct( $core, $hoverOnly = true ) {
    $this->core = $core;
    $this->hoverOnly = $hoverOnly;
    add_shortcode( 'jipangu', array( $this, 'display_jipangu' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
  }

  public function enqueue_scripts() {
    $google_maps_url = esc_url( $this->get_google_maps_url() );
    wp_register_script( 'jipangu-google-maps', $google_maps_url, array(), null, true );

    // Prepare data for the inline script
    $spots = $this->sanitize_spots_data( $this->get_spots_data() );
    $featured_image_url = esc_url( JPNG_WEB_IMAGE_URL );

    // Generate the inline JavaScript
    $inline_script = $this->generate_map_script( $spots, $featured_image_url );

    // Add the inline script after 'jipangu-google-maps'
    wp_add_inline_script( 'jipangu-google-maps', $inline_script );

    // Enqueue inline styles
    $inline_styles = $this->get_map_styles_inline();
    wp_add_inline_style( 'jipangu-google-maps', $inline_styles );
  }

  public function display_jipangu( $atts ) {
    $atts = $this->get_shortcode_attributes( $atts );
    $featured_image_url = esc_url( JPNG_WEB_IMAGE_URL );

    $responsive = empty( $atts['width'] ) && empty( $atts['height'] );
    $style = $responsive ? 'width: 100%; height: 500px;' : 'width: ' . esc_attr( $atts['width'] ) . 'px; height: ' . esc_attr( $atts['height'] ) . 'px;';

    $html = '<div id="jipangu-map" style="position: relative; ' . $style . '">';
    $html .= '<div id="jpng_map" style="width: 100%; height: 100%;"></div>';
    $html .= $this->get_jipangu_custom_overlay_container();
    $html .= '</div>';

    // Note: Removed inline styles and scripts from HTML

    wp_enqueue_script( 'jipangu-google-maps' );
    return $html;
  }

  private function get_shortcode_attributes( $atts ) {
    return shortcode_atts( array(
      'lat' => '35.681236',
      'lng' => '139.767125',
      'zoom' => '8',
      'width' => '',
      'height' => '',
    ), $atts );
  }

  private function get_google_maps_url() {
    $google_maps_api_key = sanitize_text_field( $this->core->get_option( 'google_maps_api_key' ) );
    return 'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api_key;
  }

  private function get_spots_data() {
    return $this->core->get_option( 'jipangu_data', [] );
  }

  private function sanitize_spots_data( $spots ) {
    $fresh_spots = [];
    foreach ( $spots as $spot ) {
      $fresh_spot = [
        'title' => sanitize_text_field( $spot['title'] ),
        'gps' => [
          'lng' => floatval( $spot['gps']['lng'] ),
          'lat' => floatval( $spot['gps']['lat'] ),
        ],
        'type' => sanitize_text_field( $spot['type'] ),
        'slug' => sanitize_text_field( $spot['slug'] ),
        'posts' => [
          [
            'url' => esc_url( $spot['posts'][0]['url'] ),
            'title' => sanitize_text_field( $spot['posts'][0]['title'] ),
          ],
        ],
        'featuredImageId' => sanitize_text_field( $spot['featuredImageId'] ),
      ];
      $fresh_spots[] = $fresh_spot;
    }
    return $fresh_spots;
  }

  private function get_jipangu_custom_overlay_container() {
    return '<div id="jipangu_custom_overlay" style="display: none;" data-url="">
      <img id="jipangu_custom_overlay_image" src="" alt="" style="width: 50px; height: 50px; object-fit: cover;">
      <div id="jipangu_custom_overlay_content">
        <div id="jipangu_custom_overlay_title"></div>
      </div>
    </div>';
  }

  /**
   * Generates the inline JavaScript for the map.
   *
   * @param array $spots
   * @param string $featured_image_url
   * @return string
   */
  private function generate_map_script( $spots, $featured_image_url ) {
    $spots_json = wp_json_encode( $spots );
    $responsive = $this->is_responsive();
    $hoverOnly = $this->hoverOnly ? 'true' : 'false';

    ob_start();
    ?>
    let previousClickedMarker = {
      marker: null,
      fillColor: null,
    };

    async function initMap() {
      const { Map } = await google.maps.importLibrary("maps");
      const { LatLngBounds } = await google.maps.importLibrary("core");

      const mapOptions = {
        center: { lat: <?php echo esc_js( $this->get_shortcode_attributes( [] )['lat'] ); ?>, lng: <?php echo esc_js( $this->get_shortcode_attributes( [] )['lng'] ); ?> },
        zoom: <?php echo esc_js( $this->get_shortcode_attributes( [] )['zoom'] ); ?>,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
      };

      const map = new Map( document.getElementById("jpng_map"), mapOptions );

      const spots = <?php echo $spots_json; ?>;
      const markerDefaultFillColor = "#428bc8";
      const markerFillColors = {
        "sleep": "#cea720",
        "eat": "#c16de4",
      };

      const bounds = new LatLngBounds();

      const overlay = document.getElementById("jipangu_custom_overlay");
      const overlayImage = document.getElementById("jipangu_custom_overlay_image");
      const overlayTitle = document.getElementById("jipangu_custom_overlay_title");
      if ( isTouchDevice() ) {
        overlay.addEventListener( "click", () => window.open( overlay.attributes["data-url"].value, "_blank" ) );
      }

      spots.forEach( spot => {
        const marker = createMarker( map, spot, markerFillColors, markerDefaultFillColor, overlay, overlayImage, overlayTitle, "<?php echo esc_js( $featured_image_url ); ?>" );
        bounds.extend( marker.getPosition() );
      });

      if ( spots.length > 0 ) {
        map.fitBounds( bounds, 40 );
        map.setCenter( bounds.getCenter() );
      }

      if ( <?php echo $responsive ? 'true' : 'false'; ?> ) {
        window.addEventListener( "resize", () => {
          google.maps.event.trigger( map, "resize" );
          map.setCenter( bounds.getCenter() );
        });
      }
    }

    google.maps.event.addDomListener(window, "load", initMap);

    function createMarker( map, spot, markerFillColors, markerDefaultFillColor, overlay, overlayImage, overlayTitle, featured_image_url ) {
      const svgMarker = {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 8,
        fillColor: markerFillColors[spot.type] ?? markerDefaultFillColor,
        fillOpacity: 0.65,
        strokeColor: "white",
        strokeWeight: 2,
        strokeOpacity: 0.85,
      };

      const marker = new google.maps.Marker({
        position: { lat: spot.gps.lat, lng: spot.gps.lng },
        map: map,
        icon: svgMarker,
        title: spot.posts[0].title,
      });

      if ( isTouchDevice() ) {
        marker.addListener( "click", () => {
          if ( previousClickedMarker.marker === marker ) {
            hideCustomOverlay( overlay, true );
            deactiveMarker( marker, markerFillColors[spot.type] ?? markerDefaultFillColor);
            setPreviousMarker( null, null );
            return;
          }

          if ( previousClickedMarker.marker ) {
            deactiveMarker( previousClickedMarker.marker, previousClickedMarker.fillColor);
          }
          showCustomOverlay( marker, spot, overlay, overlayImage, overlayTitle, featured_image_url );
          setPreviousMarker( marker, marker.getIcon().fillColor );
          activeMarker( marker );
        });
      } else {
        marker.addListener( "click", () => window.open( spot.posts[0].url, "_blank" ) );
        marker.addListener( "mouseover", () => {
          showCustomOverlay( marker, spot, overlay, overlayImage, overlayTitle, featured_image_url );
          activeMarker( marker );
        });
        marker.addListener( "mouseout", () => {
          hideCustomOverlay( overlay );
          deactiveMarker( marker, markerFillColors[spot.type] ?? markerDefaultFillColor );
        });
      }

      return marker;
    }

    function showCustomOverlay( marker, spot, overlay, overlayImage, overlayTitle, featured_image_url ) {
      overlayImage.src = featured_image_url + spot.featuredImageId + ".jpg";
      overlayTitle.textContent = marker.getTitle();
      overlay.style.display = "flex";
      overlay.attributes["data-url"].value = spot.posts[0].url;
    }

    function hideCustomOverlay( overlay, force = false ) {
      if ( force || <?php echo $hoverOnly ? 'true' : 'false'; ?> ) {
        overlay.style.display = "none";
        overlay.attributes["data-url"].value = "";
      }
    }

    function activeMarker( marker ) {
      marker.setIcon({
        ...marker.getIcon(),
        fillColor: "red",
      });
    }

    function deactiveMarker( marker, fillColor ) {
      marker.setIcon({
        ...marker.getIcon(),
        fillColor,
      });
    }

    function setPreviousMarker( marker, fillColor ) {
      previousClickedMarker = {
        marker,
        fillColor,
      };
    }

    function isTouchDevice() {
      return "ontouchstart" in window || navigator.maxTouchPoints > 0;
    }
    <?php
    return ob_get_clean();
  }

  /**
   * Generates the inline CSS for the map.
   *
   * @return string
   */
  private function get_map_styles_inline() {
    $responsive = $this->is_responsive();

    ob_start();
    ?>
    <?php if ( $responsive ): ?>
      #jipangu-map {
        position: relative;
        width: 100%;
        height: 500px; /* Default height for responsive */
      }

      #jipangu_custom_overlay {
        position: absolute;
        top: 20px;
        left: 20px;
        display: flex;
        align-items: center;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        padding: 5px;
        z-index: 1000;
        width: 280px;
        max-width: calc(100% - 60px);
        font-size: 13px !important;
        color: black !important;
      }

      #jipangu_custom_overlay img {
        border-radius: 4px;
        margin-right: 10px;
      }
    <?php else: ?>
      #jipangu-map {
        position: relative;
        width: <?php echo esc_attr( $this->get_shortcode_attributes( [] )['width'] ); ?>px;
        height: <?php echo esc_attr( $this->get_shortcode_attributes( [] )['height'] ); ?>px;
      }

      #jipangu_custom_overlay {
        position: absolute;
        top: 20px;
        left: 20px;
        display: flex;
        align-items: center;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        padding: 5px;
        z-index: 1000;
        width: 280px;
        max-width: calc(100% - 60px);
        font-size: 13px !important;
        color: black !important;
      }

      #jipangu_custom_overlay img {
        border-radius: 4px;
        margin-right: 10px;
      }
    <?php endif; ?>
    <?php
    return ob_get_clean();
  }

  /**
   * Determines if the map should be responsive based on shortcode attributes.
   *
   * @return bool
   */
  private function is_responsive() {
    $atts = $this->get_shortcode_attributes( [] );
    return empty( $atts['width'] ) && empty( $atts['height'] );
  }
}
?>
