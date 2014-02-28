{{ html.script('Requerimientos/cargaClientes') }}
{{ html.script('/js/Facturas/cambiarEstatus.js') }}

<script type="text/javascript">
	var url = '{{html.url('/')}}';
</script>

<section class="bar-tools">
    <ul class="unstyled">
        
        {% for a in acciones %}
        <li>
            {{ html.link(a.Accion.nombre, {'controller':'requerimientos', 'action':'cambiarestatus', 0:requerimiento_id, 1:a.Accion.id}, {'class':'btnCambiarEstatus'}) }}        
        </li>
        {% endfor %}

                
        <li class="right">
            <a href="/requerimientos/">
                <i class="icon-back left"></i>
                <span>Regresar</span>
            </a>
        </li>
        <li class="right">
            <a href="/requerimientos/excel/{{ requerimiento_id }}">
                <i class="icon-list left"></i>
                <span>Exportar</span>
            </a>
        </li>
        <li class="right">
            <a id="aBitacora" href="/requerimientos/bitacora/{{ requerimiento_id }}">
                <i class="icon-bitacora left"></i>
                <span>Bitacora</span>
            </a>
        </li>
        <li class="right" >
            <a id="aComisiones" href="/requerimientos/mostrarComisiones/{{ requerimiento_id }}">
                <i class="icon-taxation left"></i>
                <span>Comisiones</span>
            </a>
        </li>
    </ul>
</section>

<div class="form-box">
   <div class="wrap-form collapsable">
       <fieldset>
           <legend>
               <i class="icon-btn-search left"></i>
               <span>Requerimiento</span>
               <span class="btn-collapse"></span>
           </legend>
           <div class="content">
               <div class="controls ">
                <div class="row-fluid">
                    {% if requerimiento_id is defined %}
                        <div class="col12">
                            <label class="col1"><b>Asociado:</b></label>
                            <span>{{requerimiento.Asociado.razon_social}}<span>
                        </div>
                        <div class="col12">
                            <label class="col1"><b>Cliente:</b></label>
                            <span>{{requerimiento.Cliente.razon_social}}<span>
                        </div>
                        <div class="col12">
                            <label class="col1"><b>Prioridad:</b></label>
                            <span>{{requerimiento.Prioridad.nombre}}<span>
                        </div>
                        <div class="col12">
                            <label class="col1"><b>Estatus:</b></label>
                            <span>{{requerimiento.EstatusWorkflow.nombre}}<span>
                        </div>                        
                    {% else %}
                        {{ form.create('Requerimiento') }}
                        {{ form.input('porcentaje_costo_gestion', {'type':'hidden'}) }}
                        {{ form.input('porcentaje_costo_operativo', {'type':'hidden'}) }}
                        {{ form.input('porcentaje_costo_venta', {'type':'hidden'}) }}
                        {{ form.input('porcentaje_costo_venta_secundario', {'type':'hidden'}) }}
                        {{ form.input('porcentaje_costo_administrativo', {'type':'hidden'}) }}
                        {{ form.input('id', { 'type':'hidden', 'value':requerimiento_id }) }}
                        {% if esAsociado %}
                            {{ form.input('asociado_id', {'type':'hidden','value':asociados_seleccionado.0 }) }}
                        {% else %}
                            {{ form.input('asociado_id', {'class':'select2','empty':'Seleccione', 'selected':asociados_seleccionado.0, 'div':{'class':'col3'} }) }}
                        {% endif %}
                        {{ form.input('cliente_id', {'class':'select2','empty':'Seleccione', 'div':{'class':'col6'} }) }}
<div class="clear"></div>                        
                        {{ form.input('prioridad_id', {'class':'select2', 'div':{'class':'col3 top'} }) }}
                       {{ form.input('estatus_workflow', {'type':'text', 'label':'Estatus', 'readonly':true, 'value':estatus_workflow_predeterminado_nombre, 'div':{'class':'col6'}}) }}
                       {{ form.input('estatus_workflow_id', {'type':'hidden', 'value':estatus_workflow_predeterminado_id}) }}
                       <div class="clear"></div>    
                       <div class="controls right">
                           {{ form.end({'label':'Guardar','class':'btn btn-success col2'}) }}
                       </div>
                        
                    {% endif %}
                    
                </div>
               </div>

               <div class="controls ">
                   <div class="row-fluid">
                       
                       {% if requerimiento_id is defined %}
    
                        {% else %}
                            
                        {% endif %}
                   </div>
               </div>
<div class="clear"></div>
{{ form.input('porcentaje_costo_gestion', {'type':'hidden','label':'% Costo Gestion', 'div':{'class':'col3'}}) }}
{{ form.input('porcentaje_costo_operativo', {'type':'hidden','label':'% Costo Operativo', 'div':{'class':'col3'}}) }}
{{ form.input('porcentaje_costo_venta', {'type':'hidden','label':'% Costo Venta', 'div':{'class':'col3'}}) }}
{{ form.input('porcentaje_costo_venta_secundario', {'type':'hidden','label':'% Costo Venta Secundario', 'div':{'class':'col3'}}) }}
{{ form.input('porcentaje_costo_administrativo', {'type':'hidden','label':'% Costo Administrativo', 'div':{'class':'col3'}}) }}


{% if requerimiento_id  %}
    <div class="controls">    
    {{ form.input('monto_base', {'type':'hidden','div':{'class':'col3'}}) }}
    {{ form.input('monto_retencion_islr', {'type':'hidden','div':{'class':'col3'}}) }}
    {{ form.input('monto_iva', {'type':'hidden','div':{'class':'col3'}}) }}
    {{ form.input('monto_retencion_iva', {'type':'hidden','div':{'class':'col3'}}) }}
    {{ form.input('monto_total', {'type':'hidden','div':{'class':'col3'}}) }}
    
    {{ form.input('monto_costo_gestion', {'type':'hidden','div':{'class':'col3'}}) }}
    {{ form.input('monto_costo_operativo', {'type':'hidden','div':{'class':'col3'}}) }}
    {{ form.input('monto_costo_venta', {'type':'hidden','div':{'class':'col3'}}) }}
    {{ form.input('monto_costo_venta_secundario', {'type':'hidden','div':{'class':'col3'}}) }}
    {{ form.input('monto_costo_administrativo', {'type':'hidden','div':{'class':'col3'}}) }}
    </div>    
    
       
    
{%  endif %}



           </div>



       </fieldset>
   </div>
</div>



<div class="form-box">
   <div class="wrap-form collapsable">
       <fieldset>
           <legend>
               <i class="icon-add left"></i>
               <span>Listado de Facturas</span>
               <span class="btn-collapse"></span>
           </legend>
           
            <section class="bar-tools">
                <ul class="unstyled">
                    {% if estatus[requerimiento.Requerimiento.estatus_workflow_id] is defined %}
                        {% if estatus[requerimiento.Requerimiento.estatus_workflow_id] == 1 %}
                    <li class="left" >
                        <a href="/facturas/agregar/{{ requerimiento_id }}">
                            <i class="icon-form-box left"></i>
                            <span>Nueva Factura</span>
                        </a>
                    </li>
                        {% endif %}
                    {% endif %}
                    
                    <li class="left" >
                        <a id="btn-change-estatus-grupo" href="#">
                            <i class="icon-approve left"></i>
                            <span>Cambiar estatus</span>
                        </a>
                    </li>
                </ul>
            </section>
            <!---->
            <div class="wrap-form collapsable">
       {{ form.create('Factura', {'action':'index', 'id':'filters', 'class':'form form-box'}) }}
       <fieldset>
           <legend>
               <i class="icon-btn-search left"></i>
               <span>Filtros</span>
               <span class="btn-collapse"></span>
           </legend>
           <div class="content">
               <div class="controls">
                   {{ form.input('Factura.proveedor_id', {'empty':'Todos','label':'Empresa','class':'select2', 'div':{'class':'col6'}}) }}
                   {{ form.input('Factura.fecha_facturacion', {'label':'Fecha Factura','class':'date', 'div':{'class':'col6'}}) }}
               </div>
               <div class="controls">
                   {{ form.input('Factura.numero_factura', {'empty':'Todos','class':'select2', 'div':{'class':'col6'}}) }}
                   {{ form.input('Factura.numero_control', {'empty':'Todos','class':'select2', 'div':{'class':'col6'}}) }}
                   {{ form.input('Factura.estatus_workflow_id', {'label':'Estatus','type':'text','class':'select2', 'div':{'class':'col6'}}) }}
                   <button type="submit" class="btn btn-success col1 filter">buscar</button>
               </div>
           </div>
       </fieldset>
       </form>
   </div>
   <!---->
           <div class="content">
               <div class="controls">
                   <table class="table">
                       <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all-estatus" title="Seleccionar todos" /></th>
                            <th>{{ paginator.sort('Persona.razon_social', 'Empresa') }}</th>
                            <th class="numero">{{ paginator.sort('fecha_requerida', 'Fecha Requerida') }}</th>
                            <th class="numero">{{ paginator.sort('fecha_facturacion', 'Fecha Facturación') }}</th>
                            <th class="numero">{{ paginator.sort('numero_factura', 'Número de Factura') }}</th>
                            <th class="numero">{{ paginator.sort('numero_control', 'Número de Control') }}</th>
                            <th class="numero">{{ paginator.sort('monto_base', 'Monto Base') }}</th>
                            <th class="numero">{{ paginator.sort('monto_iva', 'Monto Iva') }}</th>
                            <th class="numero">{{ paginator.sort('monto_total', 'Monto Total') }}</th>
                            <th>Estatus actual</th>
                            <th>Opciones</th>                          
                        </tr>
                        </thead>
                        <tbody>
                        {% for registro in facturas %}
                        <tr>
                        	<td class="first " width="5%">
                            	{% if registro.Factura.acciones != '' %}
                              		 <input type="checkbox" name="change-estatus-factura[]" value="{{registro.Factura.id}}" estatus="{{ html.MostrarEstatus(registro.Factura.id,'id') }}" list_estatus="{{registro.Factura.acciones}}" />   
                                {% endif %}
                            </td>
                            <td>{{ registro.Persona.razon_social }}</td>
                            <td>{% if registro.Factura.fecha_requerida != '0000-00-00' %}
                            		{{ app.fechaNormal(registro.Factura.fecha_requerida) }}
                                {% endif %}                              
                            </td>
                            <td>{{ app.fechaNormal(registro.Factura.fecha_facturacion) }}</td>
                            <td>{{ registro.Factura.numero_factura }}</td>
                            <td>{{ registro.Factura.numero_control }}</td>
                            <td class="monto">{{ app.currency(registro.Factura.monto_base) }}</td>
                            <td class="monto">{{ app.currency(registro.Factura.monto_iva) }}</td>
                            <td class="monto">{{ app.currency(registro.Factura.monto_total) }}</td>
                            <td>
                            {{ html.MostrarEstatus(registro.Factura.id,'nombre') }}
                            </td>
                            <td>
                            
                            {% if html.Mostrar(groupId, 'Facturas', 'editar') > 0 %}
                               	<a href="/Facturas/editar/{{ requerimiento_id }}/{{ registro.Factura.id }}"  class="btn-small btn-delete left" title="Editar"><i class="icon-edit-warning left"></i></a>
                            {% endif %}
                                
                            {% if html.Mostrar(groupId, 'Facturas', 'duplicar') > 0 %}
                                <a href="/Facturas/duplicar/{{ requerimiento_id }}/{{ registro.Factura.id }}"  class="btn-small btn-delete left" title="Duplicar"><i class="icon-bills left"></i></a>
                            {% endif %}

                            {% if registro.Factura.numero_factura != "" %}
                                <form accept-charset="utf-8" method="post" novalidate="novalidate" action="/impresion/index" id="frm{{ registro.Factura.id }}">
                                    <input type="hidden" name="data[Impresion][proveedor_id]" value="{{ registro.Factura.proveedor_id }}">
                                    <input type="hidden" name="data[Impresion][numero_factura]" value="{{ registro.Factura.numero_factura }}">
                                    <input type="hidden" name="data[Impresion][localidad]" value="{{ registro.Ciudad.nombre }}">
                                    <input type="hidden" name="data[Impresion][condicion]" value="0">
                                    {% if (registro.EstatusWorkflow.puede_imprimir) %}
                                    <a href="/Facturas/Impresion/{{ registro.Factura.id }}" id="{{ registro.Factura.id }}" class="btn-small btn-delete left btnImprimir" title="Imprimir">
                                        <i class="icon-print left"></i>
                                    </a>
                                        {% endif %}
                                        
                                </form>
                            {% endif %}
                            
                            {% if html.Mostrar(groupId, 'Facturas', 'changeEstatus') > 0 %}
                                {% if registro.0.transicion_id != '' %}                              
                                <a factura_id="{{registro.Factura.id}}" requerimiento_id="{{requerimiento_id}}" class="btn-small btn-delete left btn-change-status" title="Cambiar Estatus" curr_estatus="{{ html.MostrarEstatus(registro.Factura.id,'nombre') }}" list_estatus="{{ registro.Factura.acciones }}"><i class="icon-approve left"></i></a>                               
                                {% endif %}
                            {% endif %}
                                
                            {% if html.Mostrar(groupId, 'Facturas', 'mostrarComisiones') > 0 %}
                                <a href="/Facturas/mostrarComisiones/{{ registro.Factura.id }}"  class="btn-small btn-delete left aComisionesFactura" title="Comisiones"><i class="icon-comisiones left" ></i></a>
                            {% endif %}
                            </td>
                            
                            
                        </tr>
                            
                        {% endfor %}
                        </tbody>
                        <tfoot>
                            <tr><td colspan="11">&nbsp;</td></tr>
                            <tr>
                                <td class="total" colspan="6" ><b>TOTALES</b></td>
                                <td class="cantidad monto"><b>{{app.currency(requerimiento.Requerimiento.monto_base)}}</b></td>
                                <td class="cantidad monto"><b>{{app.currency(requerimiento.Requerimiento.monto_iva)}}</b></td>
                                <td class="cantidad monto"><b>{{app.currency(requerimiento.Requerimiento.monto_total)}}</b></td>
                                <td></td>
                            </tr>
                        </tfoot>

                    </table>
               </div>
           </div>
       </fieldset>
   </div>
</div>


<div id="dialogoBitacora" class="hidden">

</div>


<div id="dialogoCambiarEstatusGrupo" class="hidden">
  
  	 {{ form.create('Factura', {'action': 'cambiarEstatusGrupo', 'id':'formCambiarEstatusGrupo', 'class':'form form-box'}) }}
       <input type="hidden" value="" name="list-change-estatus-factura" />
       <input type="hidden" name="requerimiento" value="{{ requerimiento_id }}" />
       <table width="60%" align="center">
            <tr align="left">
                <td>                  
                    <label><b>Cambiar Estatus:</b></label> &nbsp;
                    <select id="list-estatus-factura" name="list-estatus-factura">
                        <!-- {% for e in estatus_work_flow %}
                            <option value="{{ e.EstatusWorkflow.id }}">{{ e.EstatusWorkflow.nombre }}</option>
                         <{% endfor %}--> 
                    </select>
                </td>
            </tr>
            <tr>
                <td valign="top" colspan="2"><br />
                	<label for="FacturaObservacion"><b>Observaci&oacute;n</b></label>&nbsp;

                	<textarea name="data[Factura][observacion]" cols="40" rows="5" id="FacturaObservacion"></textarea>
                </td>    
            </tr>
            <tr>
            	<td>
                	<button type="button" id="change-estatus-grupo">Aceptar</button>
                </td>	
            </tr>
       </table>                       
   </form>
</div>



<div id="dialogoCambiarEstatus" class="hidden">
	
    {{ form.create('Factura', {'action': 'changeStatus', 'id':'form_change_estatus', 'class':'form form-box'}) }}
    	
        <input type="hidden" name="requerimiento_id" id="requerimiento_id" value=""  />
        <input type="hidden" name="factura_id" id="factura_id" value=""  />
        <input type="hidden" name="curr_estatus" id="curr_estatus" value=""  />
        <input type="hidden" name="copy_factura" id="copy_factura" value="" />
        <table width="60%" align="center">
            <tr>
                <td><label><b>Estatus actual</b></label></td>
                <td><label><b>Nuevo Estatus</b></label></td>
            </tr>
            <tr>
                <td id="curr_estatus_td"></td>
                <td>
                    <select id="list_estatus" name="list_estatus">
                    
                    </select>
                </td>
            </tr>
            <tr>
                <td valign="top" colspan="2"><br />
                	<label for="FacturaObservacion"><b>Observaci&oacute;n</b></label>&nbsp;

                	<textarea name="data[Factura][observacion]" cols="40" rows="5" id="FacturaObservacion"></textarea>
                </td>    
            </tr>
            <tr>
                <td colspan="2"><button id="trig-change-estatus">Aceptar</button></td>
            </tr>
        
        </table>
	</form>

</div>

<div id="dialog" title="Basic dialog"  class="hidden">
  <p>This is the default dialog which is useful for displaying information. The dialog window can be moved, resized and closed with the 'x' icon.</p>
</div>


<script>
$(function(){
    $('.btnImprimir').bind('click',function(e){
        e.preventDefault()
        $('#frm'+$(this).attr('id')).submit()
    })
})
</script>