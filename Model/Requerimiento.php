<?php
App::uses('AppModel', 'Model');
App::uses('CakeNumber', 'Utility');
App::uses('CakeSession', 'Model/Datasource');

class Requerimiento extends AppModel
{
    public $validate = array(
        'asociado_id' => array(
            'notempty' => array(
                'rule' => 'notempty',
                'message' => 'Este campo no puede estar vacio.',
            )
        ),
        'cliente_id' => array(
            'notempty' => array(
                'rule' => 'notempty',
                'message' => 'Este campo no puede estar vacio.',
           )
        )
    );

    public $belongsTo = array(
        'Persona' => array(
            'className' => 'Persona',
            'foreignKey' => 'asociado_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        ),
        'Cliente' => array(
            'className' => 'Persona',
            'foreignKey' => 'cliente_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        ),
        'Asociado' => array(
            'className' => 'Persona',
            'foreignKey' => 'asociado_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        ),
        'EstatusWorkflow' => array(
            'className' => 'EstatusWorkflow',
            'foreignKey' => 'estatus_workflow_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        ),
        'Prioridad' => array(
            'className' => 'Prioridad',
            'foreignKey' => 'prioridad_id',
            'conditions' => '',
            'fields' => '',
            'order' => ''
        ),
    );

    public $hasMany = array(
        'Factura' => array(
            'conditions' => '',)
			);

    public $hasOne = array(
        'Reintegro'
    );

    public $actsAs = array('Search.Searchable');

    public $filterArgs = array(
        'id' => array('type' => 'value'),
        'asociado_id' => array('type' => 'value'),
        'cliente_id' => array('type' => 'value'),
        'prioridad_id' => array('type' => 'value'),
        'estatus_workflow_id' => array('type' => 'value'),
        'grupo_id' => array('type' => 'query', 'method'=>'clienteGrupo'),
    );

    public function clienteGrupo($data=array())
    {
        $query = array('AND'=>array("Cliente.grupo_id = ".$data['grupo_id']));
        return $query;
    }

    public function getRequerimientoExpress($requerimiento_id = null)
    {
        if (!isset($requerimiento_id)){
            if (!isset($this->id)){
                $requerimiento_id=$this->id;
            }
        }
        $sql="select Factura.id, DetalleFactura.id, Proveedor.razon_social, Factura.numero_factura, Concepto.nombre, DetalleFactura.cantidad, DetalleFactura.precio_unitario  from detalle_facturas DetalleFactura
                inner join facturas Factura on Factura.id=DetalleFactura.factura_id
                inner join conceptos Concepto on  Concepto.id=DetalleFactura.concepto_id
                inner join personas Proveedor on Proveedor.id=Factura.proveedor_id
                where requerimiento_id=$requerimiento_id order by DetalleFactura.id;";
        $query = $this->query($sql);
        return $query;
    }

    public function getAcciones($usuarioGrupoId = null, $estatusInicialId = null)
    {
        $query = $this->query('SELECT
                                    Accion.nombre,
                                    Accion.id
                                FROM
                                    transiciones Transicion, acciones Accion
                                WHERE
                                    Transicion.usuario_grupo_id = ' . $usuarioGrupoId . '
                                AND
                                    Transicion.estatus_inicial_id = ' . $estatusInicialId . '
                                AND
                                    Transicion.accion_id = Accion.id
                                ');
        return $query;
    }

    public function getEstatus($groupId = null)
    {
        $query = $this->query("SELECT
                                    DISTINCT (Transicion.estatus_inicial_id) AS estatus_inicial_id,
                                    Transicion.es_modificable AS es_modificable
                                FROM
                                    transiciones AS Transicion
                                where
                                    Transicion.usuario_grupo_id = " . $groupId);
        return $query;
    }

    public function getFacturas($requerimientoId = null)
    {
        $userGroupId = CakeSession::read('UserAuth.UserGroup.id');
        
        $sql = "SELECT  Factura.id,"
             . "        Persona.razon_social,"
             . "        Factura.fecha_requerida,"
             . "        Factura.proveedor_id," 
             . "        Factura.fecha_facturacion,"
             . "        Factura.numero_factura," 
             . "        Factura.numero_control,"
             . "        Factura.monto_base,"
             . "        Factura.monto_iva,"
             . "        Factura.monto_total,"
             . "        EstatusWorkflow.puede_imprimir,"
             . "        Ciudad.nombre,"
             . "        MAX(Transicion.id) transicion_id "   
             . "FROM facturas Factura "
             . "LEFT JOIN personas Persona "
             . "ON Persona.id = Factura.proveedor_id "
             . "LEFT JOIN ciudades Ciudad "
             . "ON Ciudad.id = Persona.ciudad_id "
             . "LEFT JOIN transiciones Transicion "
             . "ON Transicion.estatus_inicial_id = Factura.estatus_workflow_id "
             . "AND Transicion.usuario_grupo_id = {$userGroupId} "
             . "AND Transicion.modulo = 'facturas' "
             . "INNER JOIN estatus_workflows EstatusWorkflow on EstatusWorkflow.id=Factura.estatus_workflow_id "
             . "WHERE Factura.requerimiento_id = {$requerimientoId} "
             . "GROUP BY Factura.id, Persona.razon_social, Factura.fecha_requerida, Factura.proveedor_id, Factura.fecha_facturacion, Factura.numero_factura, Factura.numero_control, Factura.monto_base, Factura.monto_iva, Factura.monto_total, Ciudad.nombre;";
           
        return $this->query($sql);
    }

    public function getBitacoras($registroId = null)
    {
        $query = $this->query("SELECT
                                    Auditoria.registro_id,
                                    EstatusWorkflowAnterior.nombre,
                                    EstatusWorkflowActual.nombre,
                                    Auditoria.observacion,
                                    Auditoria.valor_anterior,
                                    Auditoria.valor_nuevo,
                                    Auditoria.fecha,
                                    Usuario.username
                                FROM
                                    auditorias AS Auditoria,
                                    estatus_workflows AS EstatusWorkflowAnterior,
                                    estatus_workflows AS EstatusWorkflowActual,
                                    users AS Usuario
                                WHERE
                                    Auditoria.valor_anterior = EstatusWorkflowAnterior.id
                                AND
                                    Auditoria.valor_nuevo = EstatusWorkflowActual.id
                                AND
                                    Auditoria.usuario_id = Usuario.id
                                AND
                                    Auditoria.modelo = 'Requerimiento'
                                AND
                                    Auditoria.registro_id = " . $registroId ."
                                ORDER BY
                                    Auditoria.fecha DESC");
        return $query;
    }

    public function getTransiciones($groupId = null, $accionId = null, $estatusId = null)
    {
        $sql="SELECT
                Transicion.*,
                EstatusWorkflowActual.nombre AS estatus_actual,
                EstatusWorkflowProximo.nombre AS estatus_proximo,
                EstatusWorkflowProximo.id AS estatus_proximo_id
            FROM
                transiciones AS Transicion,
                estatus_workflows AS EstatusWorkflowActual,
                estatus_workflows AS EstatusWorkflowProximo
            WHERE
                EstatusWorkflowActual.id = Transicion.estatus_inicial_id AND
                EstatusWorkflowProximo.id = Transicion.estatus_final_id AND
                Transicion.usuario_grupo_id =  $groupId  AND
                Transicion.accion_id = $accionId AND
                Transicion.activo = 1 AND
                Transicion.estatus_inicial_id = $estatusId AND EstatusWorkflowActual.modulo='requerimientos'";
        $query = $this->query($sql);
        return $query;
    }

    public function getUsuariosEmail($estatus_id = null)
    {
        $strQuery = $this->query("SELECT User.email
                                  FROM users User
                                  WHERE User.user_group_id
                                  IN (
                                    SELECT t.`usuario_grupo_id`
                                    FROM `transiciones` t
                                    WHERE `estatus_inicial_id` =2
                                    GROUP BY t.usuario_grupo_id
                                  )");
        return $strQuery;
    }

    public function getAccion($accion_id = null)
    {
        $strQuery = $this->query("SELECT nombre
                                  FROM acciones Accion
                                  WHERE Accion.id = " . $accion_id);
        return $strQuery[0]['Accion']['nombre'];
    }

    public function actualizaDatosRequerimiento($data = null)
    {
        $porcentajeRetencionIva = $this->Persona->find('first',
                                         array('conditions' =>
                                           array('Persona.id' => $data['Requerimiento']['cliente_id']),
                                                 'fields' => 'porcentaje_retencion_iva',
                                                                ));
        $MontoIva = $this->Factura->find('all',
                                    array('conditions' =>
                                      array('requerimiento_id' =>
                                            $data['Requerimiento']['id']),
                                            'fields' => array('id',
                                                              'monto_iva',
                                                              'monto_base')
                                           )
                                  );
        foreach ($MontoIva as $MontoIva) {
            $monto_costo_gestion = $MontoIva['Factura']['monto_base'] *
                                   $data['Requerimiento']['porcentaje_costo_gestion'] /
                                   100;
            $monto_costo_operativo = $MontoIva['Factura']['monto_base'] *
                                     $data['Requerimiento']['porcentaje_costo_operativo'] /
                                     100;
            $monto_costo_venta = (!is_null($data['Requerimiento']['porcentaje_costo_venta']) && $data['Requerimiento']['porcentaje_costo_venta'] != 0)?
                                    ($monto_costo_gestion - $monto_costo_operativo) / $data['Requerimiento']['porcentaje_costo_venta']:0;
            $monto_costo_venta_secundario = (!is_null($data['Requerimiento']['porcentaje_costo_venta_secundario']) && $data['Requerimiento']['porcentaje_costo_venta_secundario'] != 0)?
                                                ($monto_costo_gestion - $monto_costo_operativo) * $data['Requerimiento']['porcentaje_costo_venta_secundario']:0;
            $monto_costo_administrativo = $monto_costo_venta - $monto_costo_venta_secundario;
            $facturas = array('id' => $MontoIva['Factura']['id'],
                              'porcentaje_retencion_iva' => $porcentajeRetencionIva['Persona']['porcentaje_retencion_iva'],
                              'monto_retencion_iva' => $MontoIva['Factura']['monto_iva'] *
                                                       $porcentajeRetencionIva['Persona']['porcentaje_retencion_iva'] /
                                                       100,
                              'monto_costo_gestion' => $monto_costo_gestion,
                              'monto_costo_operativo' => $monto_costo_operativo,
                              'monto_costo_venta' => $monto_costo_venta,
                              'monto_costo_venta_secundario' => $monto_costo_venta_secundario,
                              'monto_costo_administrativo' => $monto_costo_administrativo,
                            );
            $fact = ClassRegistry::init('Factura');
            $fact->save($facturas);
        }

        $montosFactura = $this->montosFacturas($data['Requerimiento']['id']);
        if (count($montosFactura) > 0) {
            $requerimientos = array('Requerimiento' => array(
                                                'id' => $montosFactura[0]['Factura']['requerimiento_id'],
                                                'monto_costo_gestion' => $montosFactura[0][0]['monto_costo_gestion'],
                                                'monto_costo_operativo' => $montosFactura[0][0]['monto_costo_operativo'],
                                                'monto_costo_venta' => $montosFactura[0][0]['monto_costo_venta'],
                                                'monto_costo_venta_secundario' => $montosFactura[0][0]['monto_costo_venta_secundario'],
                                                'monto_costo_administrativo' => $montosFactura[0][0]['monto_costo_administrativo'],
                                                'monto_base' => $montosFactura[0][0]['monto_base'],
                                                'monto_iva' => $montosFactura[0][0]['monto_iva'],
                                                'monto_total' => $montosFactura[0][0]['monto_total'],
                                                'monto_retencion_islr' => $montosFactura[0][0]['monto_retencion_islr'],
                                                'monto_retencion_iva' => $montosFactura[0][0]['monto_retencion_iva'],
                                                )
                             );
            $reque = ClassRegistry::init('Requerimiento');
            $reque->save($requerimientos);
        }
    }

    public function montosFacturas($requerimiento_id = null)
    {
        $Query = $this->Factura->query('
                                SELECT
                                    Factura.requerimiento_id,
                                    SUM(Factura.monto_base) AS monto_base,
                                    SUM(Factura.monto_retencion_islr) AS monto_retencion_islr,
                                    SUM(Factura.monto_iva) AS monto_iva,
                                    SUM(Factura.monto_retencion_iva) AS monto_retencion_iva,
                                    SUM(Factura.monto_total) AS monto_total,
                                    SUM(Factura.monto_base * Requerimiento.porcentaje_costo_gestion) / 100 AS monto_costo_gestion,
                                    SUM(Factura.monto_base * Requerimiento.porcentaje_costo_operativo) / 100 AS monto_costo_operativo,
                                    CASE WHEN porcentaje_costo_venta <> 0 THEN SUM((Factura.monto_base * Requerimiento.porcentaje_costo_gestion) / 100 - (Factura.monto_base * Requerimiento.porcentaje_costo_operativo) / 100) / porcentaje_costo_venta ELSE 0 END AS monto_costo_venta,
                                    CASE WHEN porcentaje_costo_venta_secundario <> 0 THEN SUM((Factura.monto_base * Requerimiento.porcentaje_costo_gestion) / 100 - (Factura.monto_base * Requerimiento.porcentaje_costo_operativo) / 100) * porcentaje_costo_venta_secundario ELSE 0 END AS monto_costo_venta_secundario,
                                    CASE WHEN porcentaje_costo_venta <> 0 THEN SUM((Factura.monto_base * Requerimiento.porcentaje_costo_gestion) / 100 - (Factura.monto_base * Requerimiento.porcentaje_costo_operativo) / 100) / porcentaje_costo_venta ELSE 0 END -
                                    CASE WHEN porcentaje_costo_venta_secundario <> 0 THEN SUM((Factura.monto_base * Requerimiento.porcentaje_costo_gestion) / 100 - (Factura.monto_base * Requerimiento.porcentaje_costo_operativo) / 100) * porcentaje_costo_venta_secundario ELSE 0 END
                                    AS monto_costo_administrativo
                                FROM
                                    facturas AS Factura,
                                    requerimientos AS Requerimiento
                                WHERE
                                    Requerimiento.id = Factura.requerimiento_id
                                AND
                                    Factura.requerimiento_id = ' . $requerimiento_id . '
                                GROUP BY Factura.requerimiento_id');
        return $Query;
    }

    public function duplicaRequerimientos($data, $id)
    {
        $asociado_id = $data['Requerimiento']['asociado_id'];
        $cliente_id = $data['Requerimiento']['cliente_id'];
        $porcentaje_costo_gestion = $data['Requerimiento']['porcentaje_costo_gestion'] == 0 ? 0 : number_format($data['Requerimiento']['porcentaje_costo_gestion']);
        $porcentaje_costo_operativo = $data['Requerimiento']['porcentaje_costo_operativo'] == 0 ? 0 : number_format($data['Requerimiento']['porcentaje_costo_operativo']);
        $porcentaje_costo_venta = empty($data['Requerimiento']['porcentaje_costo_venta']) ? 0 : number_format($data['Requerimiento']['porcentaje_costo_venta']);
        $porcentaje_costo_venta_secundario = empty($data['Requerimiento']['porcentaje_costo_venta_secundario']) ? 0 : number_format($data['Requerimiento']['porcentaje_costo_venta_secundario']);
        $porcentaje_costo_administrativo = empty($data['Requerimiento']['porcentaje_costo_administrativo']) ? 0 : number_format($data['Requerimiento']['porcentaje_costo_administrativo']);

        $strSQL = "INSERT INTO requerimientos"
                . "(asociado_id, cliente_id, estatus_workflow_id, prioridad_id, "
                . "porcentaje_costo_gestion, porcentaje_costo_operativo, porcentaje_costo_venta, "
                . "porcentaje_costo_venta_secundario, porcentaje_costo_administrativo, relacion) "
                . "VALUES "
                . "({$asociado_id}, {$cliente_id}, 1, 1, "
                . "{$porcentaje_costo_gestion}, {$porcentaje_costo_operativo}, {$porcentaje_costo_venta}, "
                . "{$porcentaje_costo_venta_secundario}, {$porcentaje_costo_administrativo}, {$id});";


        return $this->query($strSQL);
    }

    public function getInstruccionesPagos($id = null)
    {
        $strQuery = $this->query("SELECT
                                    InstruccionPago.*, Persona.razon_social, EstatusInstruccionPago.nombre
                                  FROM
                                    instruccion_pagos InstruccionPago,
                                    estatus_intruccion_pagos EstatusInstruccionPago,
                                    personas Persona
                                  WHERE
                                    InstruccionPago.estatus_instruccion_pago_id = EstatusInstruccionPago.id
                                    AND InstruccionPago.proveedor_id = Persona.id
                                    AND requerimiento_id = " . $id
                                 );
        return $strQuery;
    }
    public function getTipoInstrumentos()
    {
        $strQuery = $this->query("SELECT
                                    TipoInstrumento.nombre, TipoInstrumento.id
                                  FROM
                                    tipo_instrumentos TipoInstrumento
                                 ");
        return $strQuery;
    }
    public function getBancos()
    {
        $strQuery = $this->query("SELECT
                                    Banco.nombre, Banco.id
                                  FROM
                                    bancos Banco
                                 ");
        return $strQuery;
    }

    public function getRequerimientoCompleto($id=null)
    {
        $filtro="";
        if ($id){
            $filtro = " WHERE Requerimiento.id=$id ";
        }
        $sql="select
                Requerimiento.*,
                Asociado.razon_social, Asociado.tipo_documento_id, Asociado.documento, Asociado.codigo,
                Cliente.razon_social, Cliente.tipo_documento_id, Cliente.documento, Cliente.codigo, Cliente.direccion, Cliente.porcentaje_retencion_iva,
                Requerimiento.monto_base, Requerimiento.monto_iva, Requerimiento.monto_total,
                Proveedor.razon_social, Proveedor.tipo_documento_id, Proveedor.documento,
                Factura.id, Factura.numero_factura, DetalleFactura.cantidad, Concepto.nombre, Factura.monto_base, Factura.monto_iva, Factura.monto_total,
                Factura.monto_costo_gestion, Factura.monto_retencion_iva, Factura.monto_retencion_islr,
                Concepto.id, DetalleFactura.id, DetalleFactura.precio_unitario,
                DetalleFactura.monto_base, DetalleFactura.monto_iva, DetalleFactura.monto_total, DetalleFactura.descripcion,
                Factura.fecha_requerida,
                Islr.valor
                from requerimientos Requerimiento
                inner join facturas Factura on Requerimiento.id=Factura.requerimiento_id
                inner join detalle_facturas DetalleFactura on Factura.id=DetalleFactura.factura_id
                inner join personas Asociado on Asociado.id=Requerimiento.asociado_id
                inner join personas Cliente on Cliente.id=Requerimiento.cliente_id
                inner join personas Proveedor on Proveedor.id=Factura.proveedor_id
                inner join conceptos Concepto on Concepto.id=DetalleFactura.concepto_id
                inner join impuestos Islr on Islr.id=Concepto.impuesto_id $filtro order by DetalleFactura.id";
        $res=$this->query($sql);

        $sql="select
                Requerimiento.id,
                sum(DetalleFactura.monto_base) as total_base,
                sum(DetalleFactura.monto_iva) as total_iva,
                sum(DetalleFactura.monto_total) as total,
                sum(DetalleFactura.monto_base*Islr.valor/100) as total_ret_islr,
                sum(DetalleFactura.monto_iva*Cliente.porcentaje_retencion_iva/100) as total_ret_iva,
                sum(DetalleFactura.monto_iva)-sum(DetalleFactura.monto_iva*Cliente.porcentaje_retencion_iva/100) as total_iva_pagado,
                sum(DetalleFactura.monto_total)-sum(DetalleFactura.monto_base*Islr.valor/100)-sum(DetalleFactura.monto_iva*Cliente.porcentaje_retencion_iva/100) as total_cliente_pagado,
                sum(DetalleFactura.monto_base)*Requerimiento.porcentaje_costo_gestion/100 as total_monto_costo_gestion
                from requerimientos Requerimiento
                inner join facturas Factura on Requerimiento.id=Factura.requerimiento_id
                inner join detalle_facturas DetalleFactura on Factura.id=DetalleFactura.factura_id
                inner join personas Cliente on Cliente.id=Requerimiento.cliente_id
                inner join conceptos Concepto on Concepto.id=DetalleFactura.concepto_id
                inner join impuestos Islr on Islr.id=Concepto.impuesto_id $filtro group by 1";
        $ras=$this->query($sql);
        $totales=array();
        foreach ($ras as $valor){
            $totales[$valor['Requerimiento']['id']]=$valor;
        }
        $conceptos="";
        $factura="";
        $resp=array();
        foreach ($res as $value){

            $id=$value['Requerimiento']['id'];
            $detalleFacturaId = $value['DetalleFactura']['id'];
            $resp['requerimiento'][$id]['detalle']=$value['Requerimiento'];
            $resp['requerimiento'][$id]['asociado'] = $value['Asociado'];
            $resp['requerimiento'][$id]['totales'] = $totales[$id];
            $resp['requerimiento'][$id]['cliente'] = $value['Cliente'];
            $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['datos'] = $value['Factura'];
            $ivaPagado = $value['Factura']['monto_iva']-$value['Factura']['monto_retencion_iva'];
            $pagoNetoCliente = $value['Factura']['monto_total']-$value['Factura']['monto_retencion_iva']-$value['Factura']['monto_retencion_islr'];
            $montoCostoGestion=$value['Factura']['monto_costo_gestion'];
            $pagoProveedorEstrategico=$value['Factura']['monto_base']-$ivaPagado-$montoCostoGestion;
            $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['datos']['iva_pagado'] = $ivaPagado;
            $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['datos']['pago_neto_cliente'] = $pagoNetoCliente;
            $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['datos']['monto_costo_gestion'] = $montoCostoGestion;
            $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['datos']['pago_proveedor_estrategico'] = $pagoProveedorEstrategico;
            $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['datos']['proveedor'] = $value['Proveedor'];
            $retIslr=$value['DetalleFactura']['monto_base']*$value['Islr']['valor']/100;
            $retIva=$value['DetalleFactura']['monto_iva']*$value['Cliente']['porcentaje_retencion_iva']/100;

            if ($factura!=$value['Factura']['id']){
                $factura=$value['Factura']['id'];
                $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['conceptos']=$conceptos;
                $conceptos="";
            }

            $conceptos .= "* ".$value['Concepto']['nombre']. " ".$value['DetalleFactura']['descripcion']."\n";
                $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['conceptos']=$conceptos;

            $resp['requerimiento'][$id]['factura'][$value['Factura']['id']]['detalle'][$detalleFacturaId] =  array(
                'cantidad'=>$value['DetalleFactura']['cantidad'],
                'concepto'=>$value['Concepto']['nombre'],
                'monto_base'=>$value['DetalleFactura']['monto_base'],
                'monto_iva'=>$value['DetalleFactura']['monto_iva'],
                'monto_total'=>$value['DetalleFactura']['monto_total'],
                'monto_costo_gestion'=>$value['DetalleFactura']['monto_total']*$value['Requerimiento']['porcentaje_costo_gestion']/100,
                'ret_islr'=>$retIslr,
                'ret_iva'=>$retIva,
                'pago_neto'=>$value['DetalleFactura']['monto_total']-$retIslr-$retIva,
                );
        }
        $resp['totales']=$ras;

        return $resp;
    }

    /**
     *
     * Actualizar Montos Requerimientos
     *
     * @param Integer $requerimientoId
     *
     * @author Israel Simmons <israel.simmons@progressumit.com>
     */
    public function actualizarSaldos($requerimientoId)
    {
        $sql = "UPDATE requerimientos "

             . "INNER JOIN ("
             . "    SELECT  facturas.requerimiento_id, "
             . "            SUM(facturas.monto_base) monto_base, "
             . "            SUM(facturas.monto_iva) monto_iva, "
             . "            SUM(facturas.monto_total) monto_total "
             . "    FROM facturas "
             . "    INNER JOIN estatus_workflows " 
             . "    ON estatus_workflows.id = facturas.estatus_workflow_id "
             . "    WHERE estatus_workflows.es_anulada != 1 " 
             . "    AND estatus_workflows.es_eliminada != 1 "
             . "    GROUP BY facturas.requerimiento_id) facturas "
             . "ON facturas.requerimiento_id = requerimientos.id "

             . "SET requerimientos.monto_base = facturas.monto_base, "
             . "    requerimientos.monto_iva = facturas.monto_iva, "
             . "    requerimientos.monto_total = facturas.monto_total "

             . "WHERE requerimientos.id = {$requerimientoId};";

        $this->query($sql);
    }
	
/*	 public function paginate($conditions, $id,$fields, $order, $limit, $page = 1, $recursive = null, $extra = array()){

	print_r($conditions);
    
	   $sql = "SELECT  Factura.id,"
             . "        Persona.razon_social,"
             . "        Factura.fecha_requerida,"
             . "        Factura.proveedor_id," 
             . "        Factura.fecha_facturacion,"
             . "        Factura.numero_factura," 
             . "        Factura.numero_control,"
             . "        Factura.monto_base,"
             . "        Factura.monto_iva,"
             . "        Factura.monto_total,"
             . "        EstatusWorkflow.puede_imprimir,"
             . "        Ciudad.nombre,"
             . "        MAX(Transicion.id) transicion_id "   
             . "FROM facturas Factura "
             . "LEFT JOIN personas Persona "
             . "ON Persona.id = Factura.proveedor_id "
             . "LEFT JOIN ciudades Ciudad "
             . "ON Ciudad.id = Persona.ciudad_id "
             . "LEFT JOIN transiciones Transicion "
             . "ON Transicion.estatus_inicial_id = Factura.estatus_workflow_id "
             . "AND Transicion.usuario_grupo_id = {$userGroupId} "
             . "AND Transicion.modulo = 'facturas' "
             . "INNER JOIN estatus_workflows EstatusWorkflow on EstatusWorkflow.id=Factura.estatus_workflow_id "
             . "WHERE $conditions "
             . "GROUP BY Factura.id, Persona.razon_social, Factura.fecha_requerida, Factura.proveedor_id, Factura.fecha_facturacion, Factura.numero_factura, Factura.numero_control, Factura.monto_base, Factura.monto_iva, Factura.monto_total, Ciudad.nombre;";
           
        return $this->query($sql);
	   
	   // return $this->getFacturas($conditions,$limit,$page);
    }
    /*
    public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
        $results = $this->getFacturas($conditions);
        return count($results);
    }*/

}
