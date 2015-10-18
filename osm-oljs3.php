<?php
/*  (c) Copyright 2015  MiKa (wp-osm-plugin.HanBlog.Net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Osm_OLJS3
{
  function addTileLayer($a_LayerName, $a_Type, $a_OverviewMapZoom, $a_MapControl, $a_ExtType, $a_ExtName, $a_ExtAddress, $a_ExtInit, $a_theme){
    Osm::traceText(DEBUG_INFO, "addTileLayer V3(".$a_LayerName.",".$a_Type.",".$a_OverviewMapZoom.")");
    $TileLayer = '';
    if ($a_Type == "osm"){
      $TileLayer .= '
      var raster = new ol.layer.Tile({
        source: new ol.source.OSM({})
      });';
    }

    else if ($a_Type == "stamen_toner"){
      $TileLayer .= '
      var raster = new ol.layer.Tile({
        source: new ol.source.Stamen({
            attributions: [
              new ol.Attribution({
                html: "MAP tiles by <a href=\"http://stamen.com/\">Stamen Design</a>, " +
                "under <a href=\"http://creativecommons.org/licenses/by/3.0/\">CC BY" +
                " 3.0</a>."
              }),
              ol.source.OSM.ATTRIBUTION
            ],
            layer: "toner"
          })
        });
      ';
      }
      else if ($a_Type == "stamen_watercolor"){
        $TileLayer .= '
          var raster = new ol.layer.Tile({
            source: new ol.source.Stamen({layer: "watercolor"})
          });';
      }
      else if ($a_Type == "stamen_terrain-labels"){
        $TileLayer .= '
        var raster = new ol.layer.Tile({
          source: new ol.source.Stamen({layer: "terrain-labels"})
        });';
      }
      else if ($a_Type == "openseamap"){
        $TileLayer .= '
          var raster = new ol.layer.Tile({
            source: new ol.source.OSM()
          });
          var Layer2 = new ol.layer.Tile({ 
            source: new ol.source.OSM({
              attributions: [
              new ol.Attribution({
                html: "and &copy; " +
                "<a href=\"http://www.openseamap.org/\">OpenSeaMap</a>"
              }),
              ol.source.OSM.ATTRIBUTION
              ],
              crossOrigin: null,
              url: "http://tiles.openseamap.org/seamark/{z}/{x}/{y}.png"
            })
          });';
      }
      else if ($a_Type == "basemap_at"){

        $TileLayer .= '
      var template = "{Layer}/{Style}/{TileMatrixSet}/{TileMatrix}/{TileRow}/{TileCol}.png";
      var urls_basemap = [
        "http://maps1.wien.gv.at/basemap/" + template,
        "http://maps2.wien.gv.at/basemap/" + template,
        "http://maps3.wien.gv.at/basemap/" + template,
        "http://maps4.wien.gv.at/basemap/" + template,
        "http://maps.wien.gv.at/basemap/" + template
      ];

      // HiDPI support:
      // * Use "bmaphidpi" layer (pixel ratio 2) for device pixel ratio > 1
      // * Use "geolandbasemap" layer (pixel ratio 1) for device pixel ratio == 1
      var hiDPI = ol.has.DEVICE_PIXEL_RATIO > 1;

      var source_basemap = new ol.source.WMTS({
        projection: "EPSG:3857",
        layer: hiDPI ? "bmaphidpi" : "geolandbasemap",
        tilePixelRatio: hiDPI ? 2 : 1,
        style: "normal",
        matrixSet: "google3857",
        urls: urls_basemap,
        requestEncoding: "REST",
        tileGrid: new ol.tilegrid.WMTS({
          origin: [-20037508.3428, 20037508.3428],
            resolutions: [
            559082264.029 * 0.28E-3,
            279541132.015 * 0.28E-3,
            139770566.007 * 0.28E-3,
            69885283.0036 * 0.28E-3,
            34942641.5018 * 0.28E-3,
            17471320.7509 * 0.28E-3,
            8735660.37545 * 0.28E-3,
            4367830.18773 * 0.28E-3,
            2183915.09386 * 0.28E-3,
            1091957.54693 * 0.28E-3,
            545978.773466 * 0.28E-3,
            272989.386733 * 0.28E-3,
            136494.693366 * 0.28E-3,
            68247.3466832 * 0.28E-3,
            34123.6733416 * 0.28E-3,
            17061.8366708 * 0.28E-3,
            8530.91833540 * 0.28E-3,
            4265.45916770 * 0.28E-3,
            2132.72958385 * 0.28E-3,
            1066.36479193 * 0.28E-3
            ],
            matrixIds: [
              0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19
            ]
       }),

       attributions: [
         new ol.Attribution({
           html: "Tiles &copy; " +
           "<a href=\"http://www.basemap.at/\">basemap.at</a>"
         }),
         ol.source.OSM.ATTRIBUTION
      ],
   });

';
      }

      else {// unknwon => OSM map
        $TileLayer .= '
        var raster = new ol.layer.Tile({
          source: new ol.source.OSM()
        });';
      }
    if (strtolower($a_MapControl[0])== 'fullscreen') {
      $TileLayer .= '
      var Controls = ol.control.defaults().extend([
          new ol.control.FullScreen()
      ]); ';
    }

      return $TileLayer;
  }

  function checkControlType($a_MapControl){
    foreach ( $a_MapControl as $MapControl ){
	  Osm::traceText(DEBUG_INFO, "Checking the Map Control for OL3");
	  $MapControl = strtolower($MapControl);

	  if (( $MapControl != 'control') && ($MapControl != 'fullscreen') && ($MapControl != 'mouseposition')&& ($MapControl != 'rotate')&& ($MapControl != 'scaleline')&& ($MapControl != 'zoom')&& ($MapControl != 'zoomslider')&& ($MapControl != 'zoomtoextent') && ($MapControl != 'no') && ($MapControl != 'mouseposition') && ($MapControl != 'off')) {
	    Osm::traceText(DEBUG_ERROR, "e_invalid_control");
	    $a_MapControl[0]='No';
	  }
    }
    return $a_MapControl;
  }

  function addVectorLayer($a_MapName, $a_FileName, $a_Colour, $a_Type, $a_Counter, $a_MarkerName)
  {
    Osm::traceText(DEBUG_INFO, "addVectorLayer V3(".$a_LayerName.",".$a_Type.",".$a_OverviewMapZoom.")");
    $VectorLayer = '';
    $VectorLayer .= '
    var style'.$a_Counter.' = {
      "Point": [new ol.style.Style({
          
          image: new ol.style.Icon({
            anchor: [0.5, 41],
            anchorXUnits: "fraction",
            anchorYUnits: "pixels",
            opacity: 0.75,
            src: "'.OSM_PLUGIN_ICONS_URL.$a_MarkerName.'"
          })
      })],
	  	  
      "LineString": [new ol.style.Style({
        stroke: new ol.style.Stroke({
          color: "'.$a_Colour.'",
          width: 8
        })
      })],
      "MultiLineString": [new ol.style.Style({
        stroke: new ol.style.Stroke({
          color: "'.$a_Colour.'",
          width: 4
        })
      })]
    };';
	
	
    if ($a_Type == 'gpx'){
      $VectorLayer .= '
      var vectorL = new ol.layer.Vector({
        source: new ol.source.Vector({
	  url:"'.$a_FileName.'",
          format: new ol.format.GPX({
            extractStyles: false
          })
        }),
        style: function(feature, resolution) {return style'.$a_Counter.'[feature.getGeometry().getType()];}
      });
      ';
    }
    if ($a_Type == 'kml'){
      $VectorLayer .= '
      var vectorL = new ol.layer.Vector({
        source: new ol.source.Vector({
	  url:"'.$a_FileName.'",
          format: new ol.format.KML()
        })
      });';
    }
    $VectorLayer .= $a_MapName.'.addLayer(vectorL);';
  return $VectorLayer;
  }

}
?>
