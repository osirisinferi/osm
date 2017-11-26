<?php
if ($sc_args->getMap_event() == 'MarkerSC'){
  $output.= 'MetaboxEventhandler.MarkerSC('.$MapName.');';
} 
else if ($sc_args->getMap_event() == 'FileSC'){
  $output .= 'MetaboxEventhandler.FileSC('.$MapName.');';
}
else if ($sc_args->getMap_event() == 'TaggedPostsSC'){
  $output .= 'MetaboxEventhandler.TaggedPostsSC('.$MapName.');';
}
else if ($sc_args->getMap_event() == 'SetGeotag'){
  $output .= 'MetaboxEventhandler.SetGeotag('.$MapName.','.$post->ID.');';
}
else if ($sc_args->getMap_event() == 'AddMarker'){
  $output .= 'MetaboxEventhandler.AddMarker('.$MapName.','.$post->ID.');';
} 
?>