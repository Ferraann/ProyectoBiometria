package com.example.grupo5.aitherapp.pojos;

//------------------------------------------------------------------
// Fichero: PojoRespuestaServidor
// Autor: Pablo Chasi
// Fecha: 29/10/2025
//------------------------------------------------------------------
// Descripción:
//  Esta clase lo que pretende es procesar la respuesta del servidor
//  a las diversa
//------------------------------------------------------------------

public class PojoRespuestaServidor {
    private String status;
    private String mensaje;
    private PojoUsuario usuario;

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public String getMensaje() {
        return mensaje;
    }

    public void setMensaje(String mensaje) {
        this.mensaje = mensaje;
    }

    public PojoUsuario getUsuario() {
        return usuario;
    }

    public void setUsuario(PojoUsuario usuario) {
        this.usuario = usuario;
    }
}
