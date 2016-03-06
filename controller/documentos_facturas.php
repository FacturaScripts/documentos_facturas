<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('documento_factura.php');

/**
 * Description of documentos_facturas
 *
 * @author carlos
 */
class documentos_facturas extends fs_controller
{
   public $documentos;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Documentos', 'ventas', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      $this->share_extension();
      
      $this->check_documentos();
      $this->documentos = array();
      
      if( isset($_GET['folder']) AND isset($_GET['id']) )
      {
         if( isset($_POST['upload']) )
         {
            $this->upload_documento();
         }
         else if( isset($_GET['delete']) )
         {
            $this->delete_documento();
         }
         
         $this->documentos = $this->get_documentos();
      }
   }
   
   private function upload_documento()
   {
      if( is_uploaded_file($_FILES['fdocumento']['tmp_name']) )
      {
         $nuevon = $this->random_string(6).'_'.$_FILES['fdocumento']['name'];
         
         if( copy($_FILES['fdocumento']['tmp_name'], 'documentos/'.$nuevon) )
         {
            $doc = new documento_factura();
            $doc->ruta = 'documentos/'.$nuevon;
            $doc->nombre = $_FILES['fdocumento']['name'];
            $doc->tamano = filesize(getcwd().'/'.$doc->ruta);
            $doc->usuario = $this->user->nick;
            
            if($_GET['folder'] == 'facturascli')
            {
               $doc->idfactura = $_GET['id'];
            }
            else if($_GET['folder'] == 'facturasprov')
            {
               $doc->idfacturaprov = $_GET['id'];
            }
            
            if( $doc->save() )
            {
               $this->new_message('Documentos añadido correctamente.');
            }
            else
            {
               $this->new_error_msg('Error al asignar el archivo.');
               @unlink($doc->ruta);
            }
         }
         else
         {
            $this->new_error_msg('Error al mover el archivo.');
         }
      }
   }
   
   private function delete_documento()
   {
      $doc0 = new documento_factura();
      
      $documento = $doc0->get($_GET['delete']);
      if($documento)
      {
         if( $documento->delete() )
         {
            $this->new_message('Documento eliminado correctamente.');
            @unlink($documento->ruta);
         }
         else
         {
            $this->new_error_msg('Error al eliminar el documento.');
         }
      }
      else
      {
         $this->new_error_msg('Documento no encontrado.');
      }
   }
   
   private function share_extension()
   {
      $extensiones = array(
          array(
              'name' => 'documentos_facturascli',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_factura',
              'type' => 'tab',
              'text' => '<span class="glyphicon glyphicon-file" aria-hidden="true" title="Documentos"></span>',
              'params' => '&folder=facturascli'
          ),
          array(
              'name' => 'documentos_facturasprov',
              'page_from' => __CLASS__,
              'page_to' => 'compras_factura',
              'type' => 'tab',
              'text' => '<span class="glyphicon glyphicon-file" aria-hidden="true" title="Documentos"></span>',
              'params' => '&folder=facturasprov'
          ),
      );
      foreach($extensiones as $ext)
      {
         $fsext = new fs_extension($ext);
         $fsext->save();
      }
   }
   
   public function url()
   {
      if( isset($_GET['folder']) AND isset($_GET['id']) )
      {
         return 'index.php?page='.__CLASS__.'&folder='.$_GET['folder'].'&id='.$_GET['id'];
      }
      else
         return parent::url();
   }
   
   private function get_documentos()
   {
      $doc = new documento_factura();
      if($_GET['folder'] == 'facturascli')
      {
         return $doc->all_from('idfactura', $_GET['id']);
      }
      else if($_GET['folder'] == 'facturasprov')
      {
         return $doc->all_from('idfacturaprov', $_GET['id']);
      }
      else
      {
         return array();
      }
   }
   
   private function check_documentos()
   {
      if( !file_exists('documentos') )
      {
         mkdir('documentos');
      }
      
      if( isset($_GET['folder']) AND isset($_GET['id']) )
      {
         /// comprobamos la antigua rura
         $folder = 'tmp/'.FS_TMP_NAME.'documentos_facturas/'.$_GET['folder'].'/'.$_GET['id'];
         if( file_exists($folder) )
         {
            foreach( scandir($folder) as $f )
            {
               if($f != '.' AND $f != '..')
               {
                  /// movemos a la nueva ruta
                  $nuevon = $this->random_string(6).'_'.(string)$f;
                  if( rename($folder.'/'.$f, 'documentos/'.$nuevon) )
                  {
                     $doc = new documento_factura();
                     $doc->ruta = 'documentos/'.$nuevon;
                     $doc->nombre = (string)$f;
                     $doc->tamano = filesize(getcwd().'/'.$doc->ruta);
                     $doc->usuario = $this->user->nick;
                     
                     if($_GET['folder'] == 'facturascli')
                     {
                        $doc->idfactura = $_GET['id'];
                     }
                     else if($_GET['folder'] == 'facturasprov')
                     {
                        $doc->idfacturaprov = $_GET['id'];
                     }
                     
                     if( !$doc->save() )
                     {
                        $this->new_error_msg('Error al mover el archivo.');
                     }
                  }
                  else
                  {
                     $this->new_error_msg('Error al mover el archivo a la nueva ruta.');
                  }
               }
            }
         }
      }
   }
}
