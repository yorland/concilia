<?php
App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');


class RequerimientosController extends AppController
{
    public $components = array('Search.Prg', 'MyUserAuth');
    public $presetVars = true;
    public $uses = array('Requerimiento','Factura','DetalleFactura','EstatusWorkflow');
    public $helpers = array('App');

    public function index()
    {
        $this->Requerimiento->validator()->remove('asociado_id');
        $this->Requerimiento->validator()->remove('cliente_id');
        
        $conditions = array();
        $groupId = $this->UserAuth->getGroupId();
        $this->Requerimiento->unbindModel(array(
                'hasMany' => array('Factura'),
            ), false
        );

        $this->Prg->commonProcess();
        if(isset($this->passedArgs['estatus_id'])){
            $this->passedArgs['estatus_workflow_id'] = $this->passedArgs['estatus_id'];
            unset($this->passedArgs['estatus_id']);
        }

        $this->paginate = array('order'=>'Requerimiento.id desc','conditions' => $this->Requerimiento->parseCriteria($this->passedArgs));

        if ($this->MyUserAuth->isAsociado()) {
            $esAsociado = TRUE;
            $asociados = $this->Requerimiento->Persona->find('list', array('conditions' => array('Persona.id = ' . $this->Session->read('UserAuth.User.persona_id'))));
            $asociados_seleccionado = array_values($this->Requerimiento->Persona->find('list', array('conditions' => array('Persona.id = ' . $this->Session->read('UserAuth.User.persona_id')), 'fields' => 'Persona.id')));
            $options['joins'] = array(
                                    array('table' => 'persona_personas',
                                    'alias' => 'PersonaPersona',
                                    'type' => '',
                                    'conditions' => array(
                                                         'Persona.id = PersonaPersona.persona_hijo_id',
                                                         ),
                                          ),
                                    );
            $options['conditions'] = array(
                'PersonaPersona.persona_padre_id' => $asociados_seleccionado,
                'PersonaPersona.tipo_relacion' => 'asociado_clientes'
            );
            $clientes = $this->Requerimiento->Persona->getAsociadoClientes2('list', $this->Session->read('UserAuth.User.persona_id'));
            $conditions[] = array('Requerimiento.asociado_id' => $this->Session->read('UserAuth.User.persona_id'));
        } else {
            $asociados = $this->Requerimiento->Persona->getAsociados('list');
            //$clientes = $this->Requerimiento->Persona->find('list', array('conditions' => array('Clasificacion.es_cliente = 1')));
            $clientes = array();
        }
        $grupos = $this->Requerimiento->Persona->Grupo->find('list');
        $rubros = $this->Requerimiento->Persona->Rubro->find('list');
        $prioridades = $this->Requerimiento->Prioridad->find('list');
        $estatuses = $this->Requerimiento->EstatusWorkflow->find('list');
        $this->set(compact('asociados', 'clientes', 'asociados_seleccionado', 'prioridades', 'estatuses', 'groupId','esAsociado', 'grupos', 'rubros'));
        $this->set('requerimientos', $this->paginate($conditions));
    }

    public function bitacora($id = null)
    {
        $bitacoras = $this->Requerimiento->getBitacoras($id);
        $this->set('bitacoras', $bitacoras);

    }

    public function cambiarEstatus($id = null, $accion_id = null, $ocultar = null)
    {
        $camposGuardar = array('estatus_workflow_id');

        $this->Requerimiento->id = $id;

        if ($this->request->is('post') || $this->request->is('put')) {
            $this->Requerimiento->create();
            $estatus_workflow_actual = $this->request->data['Requerimiento']['estatus_workflow_actual'];
            $estatus_workflow_proximo = $this->request->data['Requerimiento']['estatus_workflow_proximo'];
            $observacion = $this->request->data['Requerimiento']['observacion'];
            $accion = $this->Requerimiento->getAccion($accion_id);
            if ($accion == "Aprobar") {
                $accion = "Aprobado";
            } else {
                $accion = "Rechazado";
            }
            $usuario = $this->UserAuth->getUser();

            array_splice($this->request->data['Requerimiento'], 1, 3);

            $textoEmail = "El usuario " . $usuario['User']['first_name'] . " " . $usuario['User']['last_name'] . "
                           ha " . $accion . " el requerimiento Nro. " . $id . "<br><br>
                           Fecha y hora del cambio de Estatus: " . date("d/m/Y h:i:s") ."<br>
                           Estatus Anterior: " . $estatus_workflow_actual . "<br>
                           Estatus Actual: " . $estatus_workflow_proximo . "<br>
                           Observación: " . $observacion;
            $asuntoEmail = "El requerimiento Nro: " . $id . " ha sido " . $accion;

            if ($this->Requerimiento->save($this->request->data, $validate = true, $fieldList = $camposGuardar)) {
                $usuariosEmail = $this->Requerimiento->getUsuariosEmail($this->request->data['Requerimiento']['estatus_workflow_id']);

                $this->Session->setFlash(__('Registro guardado con exito.'), 'message_successful');
                $this->redirect(array('action' => 'editar', $this->Requerimiento->id));
            } else {
                $this->Session->setFlash(__('Registro no guardado. Por favor, intente nuevamente.'), 'message_error');
                $this->redirect(array('action' => 'editar', $this->Requerimiento->id));

            }
        }
        if (!$this->Requerimiento->exists()) {
            throw new NotFoundException(__('Registro Invalido.'));
        }

        $this->request->data = $this->Requerimiento->read(null, $id);


        $transiciones = $this->Requerimiento->getTransiciones(
                                                        $this->UserAuth->getGroupId(),
                                                        $accion_id,
                                                        $this->request->data['Requerimiento']['estatus_workflow_id']);
        $this->set('transiciones', $transiciones);

        $estatus_workflow_id = $this->request->data['Requerimiento']['estatus_workflow_id'];
        $estatus_workflow_nombre = $this->Requerimiento->EstatusWorkflow->find('list',
                                                                                array('conditions' => array(
                                                                                                    'EstatusWorkflow.id' => $this->request->data['Requerimiento']['estatus_workflow_id']
                                                                                                     ),
                                                                                      'fields' => 'EstatusWorkflow.nombre'
                                                                                     )
                                                                               );
        $this->set(compact('estatus_workflow_id', 'accion_id', 'id', 'estatus_workflow_nombre', 'ocultar'));

    }

    public function agregar()
    {
        $groupId=$this->UserAuth->getGroupId();
        if ($this->MyUserAuth->isAsociado()) {
            $asociados = $this->Requerimiento->Persona->find('list', array('conditions' => array('Persona.id = ' . $this->Session->read('UserAuth.User.persona_id'))));
            $asociados_seleccionado = array_values($this->Requerimiento->Persona->find('list', array('conditions' => array('Persona.id = ' . $this->Session->read('UserAuth.User.persona_id')), 'fields' => 'Persona.id')));
            $options['joins'] = array(
                                    array('table' => 'persona_personas',
                                    'alias' => 'AsociadoCliente',
                                    'type' => '',
                                    'conditions' => array(
                                                         'Persona.id = AsociadoCliente.persona_hijo_id',
                                                         ),
                                          ),
                                    );
            $options['conditions'] = array(
                'AsociadoCliente.persona_padre_id' => $asociados_seleccionado
            );
            $options['fields'] = array('Persona.id', 'Persona.razon_social');
            
        } else {
            $asociados = $this->Requerimiento->Persona->getAsociados('list');
        }
        $prioridades = $this->Requerimiento->Prioridad->find('list');
        $prioridad_predeterminado = array_values($this->Requerimiento->Prioridad->find('list', array('conditions' => array('Prioridad.predeterminado = 1'), 'fields' => 'Prioridad.id')));
        $estatus_workflow_predeterminado_id = $this->Requerimiento->EstatusWorkflow->find('list', array('conditions' => array('EstatusWorkflow.predeterminado = 1', 'EstatusWorkflow.modulo'=>'requerimientos'), 'fields' => 'EstatusWorkflow.id'));
        $estatus_workflow_predeterminado_nombre = $this->Requerimiento->EstatusWorkflow->find('list', array('conditions' => array('EstatusWorkflow.predeterminado = 1', 'EstatusWorkflow.modulo'=>'requerimientos'), 'fields' => 'EstatusWorkflow.nombre'));
        $this->set(compact(
                          'asociados',
                          'asociados_seleccionado',
                          'clientes',
                          'prioridades',
                          'prioridad_predeterminado',
                          'estatus_workflow_predeterminado_id',
                          'estatus_workflow_predeterminado_nombre','groupId'
                          )
                  );
        $this->_guardar();
        $this->render('form');
    }

    public function editar($id = null)
    {
        $this->Factura->validator()->remove('proveedor_id');
        $this->Factura->validator()->remove('concepto_id');
		//echo "<pre>";
		//print_r($this->Requerimiento);
		$this->Requerimiento->id = $id;
        if (!$this->Requerimiento->exists()) {
            throw new NotFoundException(__('Registro Invalido.'));
        }
        $this->_guardar($id);
        if ($this->request->is('post') || $this->request->is('put')) {
            $this->Requerimiento->actualizaDatosRequerimiento($this->data);
        }
        $requerimiento = $this->Requerimiento->data;

        if ($this->MyUserAuth->isAsociado()) {
            $esAsociado = TRUE;
        }

        $user_group = $this->MyUserAuth->getUserGroup();

        $estatus_work_flow = $this->EstatusWorkflow->find('all', array('conditions' => array('EstatusWorkflow.modulo' => 'facturas', 'EstatusWorkflow.activo' => 1)));


        $facturas = $this->Requerimiento->getFacturas($id);
		
        $condiciones=array();//
		
		
		
		//$this->Prg->commonProcess();// CUANDO LO DESCOMENTO SE TRAE EL METODO DE BUSQUEDA DE REQUERIMIENTO Y NO DE FACTURA
	/*	$this->Factura->parseCriteria($this->passedArgs);
		
		if (count($this->passedArgs)>1){//AQUI ESTABA COLOCANDO EL METODO DE BUSQUEDA CON LOS PARAMTROS DE LAS FACTURAS
            //pr($this->passedArgs);
                $condiciones=array(
                'proveedor_id'=>$this->passedArgs['proveedor_id'],
                'fecha_facturacion'=>$this->passedArgs['fecha_facturacion'],
                'numero_factura'=>$this->passedArgs['numero_factura'],
                'numero_control'=>$this->passedArgs['numero_control'],
                'estatus_workflow_id'=>$this->passedArgs['estatus_workflow_id'],
            );
		 }			 
	   if (isset($this->passedArgs['page'])){
            $page=$this->passedArgs['page'];
        }else{
            $page=1;
        }
        $this->data=array('Requerimiento'=>$this->passedArgs);
        $this->paginate = array('conditions' => $condiciones);
        //$facturas=$this->paginate();//ESTA PAGINATE VA AL MODELO REQUERIMIENTO Y DEBERIA DE REALIZAR LA CONSULTA
		
		*/
		
		
		$cont = 0;
        foreach($facturas as $valor){
             $acciones = $this->MostrarAccionesEstatus($facturas[$cont]['Factura']['id'],'lista',$user_group);
             $facturas[$cont]['Factura']['acciones'] = $acciones;
             
             $cont++;
        }

        $this->set(compact('requerimiento', 'esAsociado', 'estatus_work_flow','user_group','facturas',$this->paginate()));

         $this->render('form');

    }

    public function excel($id=null)
    {
        $this->layout = 'excel';
        $requerimientos = $this->Requerimiento->getRequerimientoCompleto($id);
        $this->set(compact('requerimientos'));
        $this->render('excel2');

    }

    public function duplicar($id = null)
    {
        $this->Requerimiento->unbindModel(array('belongsTo'=>array('EstatusWorkflow','Prioridad', 'Persona', 'Cliente', 'Asociado')));
        $this->Requerimiento->Factura->unbindModel(array('belongsTo'=>array('Impuesto','Persona','Requerimiento')));
        $this->Requerimiento->recursive = 2;
        $this->Requerimiento->id = $id;
        $this->Requerimiento->read();

        $datos['Requerimiento'] = $this->Requerimiento->data['Requerimiento'];
        $datos['Requerimiento']['Factura'] = $this->Requerimiento->data['Factura'];

        array_splice($datos['Requerimiento'], 0, 1);

        for ($i=0;$i<count($datos['Requerimiento']['Factura']);$i++){
            $datos['Requerimiento']['Factura'][$i]['copia_factura_id']=$datos['Requerimiento']['Factura'][$i]['id'];
            array_splice($datos['Requerimiento']['Factura'][$i], 0, 1);
            array_splice($datos['Requerimiento']['Factura'][$i], 2, 5);
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            array_splice($this->request->data['Requerimiento'], 0, 1);
            $datos['Requerimiento']['asociado_id']=$this->request->data['Requerimiento']['asociado_id'];
            $datos['Requerimiento']['cliente_id']=$this->request->data['Requerimiento']['cliente_id'];
            $this->Requerimiento->create();
            $requerimiento = $this->Requerimiento->save($datos['Requerimiento']);
            $this->Requerimiento->Factura->duplicaFacturas($id, $requerimiento['Requerimiento']['id']);
            $this->Requerimiento->Factura->DetalleFactura->duplicaDetalleFacturas2($requerimiento['Requerimiento']['id']);
            $this->redirect(array('action' => 'editar', $requerimiento['Requerimiento']['id']));

        } else {
            $this->_guardar($id);
        }


        $this->render('duplicar');
    }

    public function instruccionPago($id = null)
    {
        $this->Requerimiento->id = $id;
        if ($this->request->is('post') || $this->request->is('put')) {
            $this->Session->setFlash(__('Registro guardado con exito.'), 'message_successful');
            $this->redirect(array('action' => 'editar', $this->request->data['Requerimiento']['id']));
        } else {
            $this->_guardar($id);
            $instrucciones = $this->Requerimiento->getInstruccionesPagos($id);
        }
        $tipoInstrumento = $this->Requerimiento->getTipoInstrumentos();
        foreach ($tipoInstrumento as $value) {
            $tipoInstrumentos[$value['TipoInstrumento']['id']] = $value['TipoInstrumento']['nombre'];
        }
        $banco = $this->Requerimiento->getBancos();
        foreach ($banco as $value) {
            $bancoOrigenes[$value['Banco']['id']] = $value['Banco']['nombre'];
        }

        $this->set(compact('instrucciones', 'tipoInstrumentos', 'bancoOrigenes'));
        $this->render('instruccionpago');
    }

    private function _guardar($id = null)
    {
        $camposGuardar = array('asociado_id', 'cliente_id', 'prioridad_id', 'estatus_workflow_id',
                               'porcentaje_costo_gestion', 'porcentaje_costo_operativo',
                               'porcentaje_costo_venta', 'porcentaje_costo_venta_secundario',
                               'porcentaje_costo_administrativo', 'creado', 'creado_por', 'modificado', 'modificado_por');
        $groupId = $this->UserAuth->getGroupId();
        $estatus = array();
        if ($this->request->is('post') || $this->request->is('put')) {
            $this->Requerimiento->create();
            array_splice($this->request->data['Requerimiento'], 4, 1);
            $this->request->data['Requerimiento']['creado'] = date('Y-m-d H:i');
                if ($this->Requerimiento->save($this->request->data, $validate = true, $fieldList = $camposGuardar)) {
                    $this->Session->setFlash(__('Registro guardado con éxito.'), 'message_successful');
                    $this->redirect(array('action' => 'editar', $this->Requerimiento->id));
                } else {
                    $this->Session->setFlash(__('Registro no guardado. Por favor, intente nuevamente.'), 'message_error');
                }
        } else {
            if (isset($id)) {
                $this->Requerimiento->unbindModel(
                    array('hasMany' => array('Factura'), 'belongsTo' => array( 'Persona'))
                );

                $this->request->data = $this->Requerimiento->read(null, $id);

                if ($this->MyUserAuth->isAsociado()) {
                    $asociados = $this->Requerimiento->Persona->getAsociados('list');
                    $asociados_seleccionado = array_values($this->Requerimiento->Persona->find('list', array('conditions' => array('Persona.id = ' . $this->Session->read('UserAuth.User.persona_id')), 'fields' => 'Persona.id')));
                    $options['joins'] = array(
                                            array('table' => 'persona_personas',
                                            'alias' => 'PersonaPersona',
                                            'type' => '',
                                            'conditions' => array(
                                                                 'Persona.id = PersonaPersona.persona_hijo_id',
                                                                 ),
                                                  ),
                                            );
                    $options['conditions'] = array(
                        'PersonaPersona.persona_padre_id' => $asociados_seleccionado,
                        'PersonaPersona.tipo_relacion' => 'asociado_clientes'
                    );
                    $options['fields'] = array('Persona.id', 'Persona.razon_social');
                    $clientes = $this->Requerimiento->Persona->find('list', $options);
                } else {
                    $asociados = $this->Requerimiento->Persona->getAsociados('list');
                    if (isset($this->Requerimiento->data)){
                        $asociadoId=$this->Requerimiento->data['Requerimiento']['asociado_id'];
                        $options['joins'] = array(
                                                array('table' => 'persona_personas',
                                                'alias' => 'PersonaPersona',
                                                'type' => '',
                                                'conditions' => array(
                                                                     'Persona.id = PersonaPersona.persona_hijo_id',
                                                                     ),
                                                      ),
                                                );
                        $options['conditions'] = array(
                            'PersonaPersona.persona_padre_id' => $asociadoId,
                            'PersonaPersona.tipo_relacion' => 'asociado_clientes'
                        );
                        $options['fields'] = array('Persona.id', 'Persona.razon_social');
                        $clientes = $this->Requerimiento->Persona->find('list', $options);
                    }else{
                        $clientes = array();
                    }
                }
                $acciones = array();
                if ($this->request->data['Requerimiento']['monto_base'] > 0){
                    $acciones = $this->Requerimiento->getAcciones($groupId, $this->request->data['Requerimiento']['estatus_workflow_id']);
                    $this->request->data['Requerimiento']['monto_base']= CakeNumber::currency($this->request->data['Requerimiento']['monto_base'],'VEF');
                    $this->request->data['Requerimiento']['monto_retencion_islr']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_retencion_islr'],'VEF');
                    $this->request->data['Requerimiento']['monto_iva']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_iva'],'VEF');
                    $this->request->data['Requerimiento']['monto_retencion_iva']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_retencion_iva'],'VEF');
                    $this->request->data['Requerimiento']['monto_total']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_total'],'VEF');
                    $this->request->data['Requerimiento']['porcentaje_costo_gestion']=  CakeNumber::currency($this->request->data['Requerimiento']['porcentaje_costo_gestion'],'VEF');
                    $this->request->data['Requerimiento']['porcentaje_costo_operativo']=  CakeNumber::currency($this->request->data['Requerimiento']['porcentaje_costo_operativo'],'VEF');
                    $this->request->data['Requerimiento']['monto_costo_gestion']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_costo_gestion'],'VEF');
                    $this->request->data['Requerimiento']['monto_costo_operativo']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_costo_operativo'],'VEF');
                    $this->request->data['Requerimiento']['monto_costo_venta']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_costo_venta'],'VEF');
                    $this->request->data['Requerimiento']['monto_costo_venta_secundario']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_costo_venta_secundario'],'VEF');
                    $this->request->data['Requerimiento']['monto_costo_administrativo']=  CakeNumber::currency($this->request->data['Requerimiento']['monto_costo_administrativo'],'VEF');
                }
                $prioridades = $this->Requerimiento->Prioridad->find('list');
                $prioridad_predeterminado = $this->request->data['Requerimiento']['prioridad_id'];
                $estatus_workflow_predeterminado_id = $this->request->data['Requerimiento']['estatus_workflow_id'];
                $estatus_workflow_predeterminado_nombre = $this->Requerimiento->EstatusWorkflow->find('list',
                                                                        array(
                                                                         'conditions' =>
                                                                            array(
                                                                              'EstatusWorkflow.id' => $this->request->data['Requerimiento']['estatus_workflow_id'],
                                                                              'EstatusWorkflow.modulo' => 'requerimientos'
                                                                                 ),
                                                                          'fields' => 'EstatusWorkflow.nombre'
                                                                              )
                                                                                                    );
                $estatusDisponibles = $this->Requerimiento->getEstatus($groupId);
                foreach ($estatusDisponibles as $estatusDisponible):
                    $estatus[$estatusDisponible['Transicion']['estatus_inicial_id']] = $estatusDisponible['Transicion']['es_modificable'];
                endforeach;

                $proveedores = array();
                $this->set(compact(
                              'acciones',
                              'asociados',
                              'clientes',
                              'proveedores',
                              'prioridades',
                              'estatus',
                              'prioridad_predeterminado',
                              'estatus_workflow_predeterminado_id',
                              'estatus_workflow_predeterminado_nombre'
                              )
                      );
                $this->Requerimiento->unbindModel(
                    array('belongsTo' => array('Prioridad', 'Asociado', 'Cliente', 'EstatusWorkflow'))
                );
                $facturas = $this->Requerimiento->getFacturas($id);
                $this->set('facturas', $facturas);
                $this->set(compact('proveedores','groupId'));
                $this->set('requerimiento_id', $id);


            }
        }
    }

    public function delete($id = null)
    {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $this->Requerimiento->id = $id;

        if (!$this->Requerimiento -> exists()) {
            throw new NotFoundException(__('Registro Invalido.'));
        }

        if ($this->Requerimiento->delete()) {
            $this->Session->setFlash(__('Registro eliminado.'), 'message_successful');
            $this->redirect(array('action' => 'index'));
        }

        $this->Session->setFlash(__('Registro no eliminado. Por favor, intente nuevamente.'), 'message_error');
        $this->redirect(array('action' => 'index'));
    }

    public function mostrarComisiones($id=null)
    {
        $this->layout = 'ajax';
        $requerimiento = $this->Requerimiento->read(null, $id);
        $this->set(compact('requerimiento'));
    }

    public function MostrarAccionesEstatus($factura_id, $modo, $user_group)
    {
        $acciones = array();

        $factura = ClassRegistry::init('Factura');
        $estatus_work_flow = ClassRegistry::init('EstatusWorkflow');
        $cobranza_factura = ClassRegistry::init('CobranzaFactura');
        $reintegro = ClassRegistry::init('Reintegro');
        $transicion = ClassRegistry::init('Transicion');

        $factura->id = $factura_id;
        $factura->read();
        $data = $factura->data;
        switch($data['EstatusWorkflow']['es_impresa']){

            case 0:
                $arr_estatus = $this->getTransitionsByCurrStatus($data['EstatusWorkflow']['id'],$user_group);
                $acciones = $this->setAcciones($arr_estatus);
                break;

            case 1:
                $obj_cobranza_factura = $cobranza_factura->find('first', array('conditions' => array('CobranzaFactura.factura_id' => $factura_id) ));
                $obj_reintegro = $reintegro->find('first', array('conditions' => array('Reintegro.requerimiento_id' => $data['Factura']['requerimiento_id']) ));

                if(!$obj_cobranza_factura && !$obj_reintegro){

                    $new = $estatus_work_flow->find('first', array('conditions' => array('EstatusWorkflow.es_anulada' => 1, 'EstatusWorkflow.modulo' => 'facturas') ));
                    $acciones[] = array('controller' => 'Facturas', 'action' => 'changeStatus', 'title' => $new['EstatusWorkflow']['nombre'], 'curr' => $data['EstatusWorkflow']['id'], 'new' => $new['EstatusWorkflow']['id'], 'nameAction' => $new['EstatusWorkflow']['nombre'], 'es_anulada' => $new['EstatusWorkflow']['es_anulada']);
                }
                break;

        }

        if($modo == "arreglo") {
            return $acciones;
        } else {
            $lista = "";
            foreach($acciones as $valor){

                if($valor['new'] != "")
                    $lista .= $valor['new']."-".$valor['title']."-".$valor['curr']."-".$valor['action']."-".$valor['controller']."-".$valor['es_anulada'].",";
                else
                    $lista .= "0-".$valor['title']."-".$valor['curr']."-".$valor['action'].",";
            }

            return $lista;
        }

    }


    public function setAcciones($arr_estatus)
    {
        $acciones = array();

        foreach($arr_estatus as $valor){
            $action = 'changeStatus';
            $controller = 'Facturas';
            
            $acciones[] = array('controller' => $controller, 'action' => $action, 'title' => $valor['EstatusFinal']['nombre'], 'curr' => $valor['EstatusInicial']['id'], 'new' => $valor['EstatusFinal']['id'], 'nameAction' => '', 'es_anulada' => $valor['EstatusFinal']['es_anulada']);

        }

        return $acciones;
    }

    public function getTransitionsByCurrStatus($curr_estatus,$user_group)
    {
        $transicion = ClassRegistry::init('Transicion');

        $transitions = $transicion->find('all', array(
            'conditions' => array(
                'Transicion.estatus_inicial_id' => $curr_estatus, 
                'Transicion.activo' => 1, 
                'Transicion.usuario_grupo_id' => $user_group ) ));

        return $transitions;
    }
    
}
