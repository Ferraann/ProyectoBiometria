package com.example.grupo5.aitherapp.pojos;
// ------------------------------------------------------------------
// Fichero: PojoUsuario.java
// Autor: Pablo Chasi
// Fecha: 28/10/2025
// ------------------------------------------------------------------
// Descripción:
// Esta clase lo que pretende es ser un clase donde se guarde
// información basica del usuario: nombre, Apellidos, Correo, Id
//-------------------------------------------------------------------
public class PojoUsuario {
    private String id;
    private String nombre;
    private String apellidos;
    private String gmail;
    private String password;
    private String activo;
    private String accion;

    public String getId() {
        return id;
    }

    public void setId(String Id) {
        id = Id;
    }

    public String getNombre() {
        return nombre;
    }

    public void setNombre(String Nombre) {
        nombre = Nombre;
    }

    public String getApellidos() {
        return apellidos;
    }

    public void setApellidos(String Apellidos) {
        apellidos = Apellidos;
    }

    public String getCorreo() {
        return gmail;
    }

    public void setCorreo(String correo) {
        gmail = correo;
    }

    public String getContrasenya() {
        return password;
    }

    public void setContrasenya(String contrasenya) { password = contrasenya; }

    public String getActivo() {
        return activo;
    }

    public void setActivo(String Activo) {
        activo = Activo;
    }

    public String getAction() {
        return accion;
    }

    public void setAction(String action) {
        accion = action;
    }
}
