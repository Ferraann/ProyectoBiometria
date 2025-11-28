package com.example.grupo5.aitherapp.pojos;
// ------------------------------------------------------------------
// Fichero: PojoSensor.java
// Autor: Pablo Chasi
// Fecha: 28/10/2025
// ------------------------------------------------------------------
// Descripción:
// Clase POJO utilizada para representar un sensor dentro de la
// aplicación. Este objeto se usa para enviar datos a la API cuando
// se vincula un sensor a un usuario. Contiene la acción a realizar,
// la dirección MAC del sensor, el modelo y el ID del usuario que
// realiza la vinculación.
// ------------------------------------------------------------------
public class PojoSensor {
    private String accion;
    private String mac;
    private String modelo;
    private String usuario_id;

    public String getAccion() {
        return accion;
    }

    public void setAccion(String accion) {
        this.accion = accion;
    }

    public String getMac() {
        return mac;
    }

    public void setMac(String mac) {
        this.mac = mac;
    }

    public String getModelo() {
        return modelo;
    }

    public void setModelo(String modelo) {
        this.modelo = modelo;
    }

    public String getUsuario_id() {
        return usuario_id;
    }

    public void setUsuario_id(String usuario_id) {
        this.usuario_id = usuario_id;
    }
}
