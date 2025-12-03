package com.example.grupo5.aitherapp.pojos;

// ------------------------------------------------------------------
// Fichero: PojoUsuario.java
// Autor: Pablo Chasi
// Fecha: 28/10/2025
// ------------------------------------------------------------------
// Descripción:
// Clase que guarda información básica del usuario: nombre, apellidos, correo, id, estado, acción y distancia
//-------------------------------------------------------------------

public class PojoUsuario {
    private String id;
    private String nombre;
    private String apellidos;
    private String gmail;
    private String password;
    private String activo;
    private String accion;
    private float distancia;
    private String fecha; // <-- agregado

    // Distancia
    public float getDistancia() { return distancia; }
    public void setDistancia(float distancia) { this.distancia = distancia; }

    // Fecha
    public String getFecha() { return fecha; }
    public void setFecha(String fecha) { this.fecha = fecha; }

    // Id
    public String getId() { return id; }
    public void setId(String id) { this.id = id; }

    // Nombre
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }

    // Apellidos
    public String getApellidos() { return apellidos; }
    public void setApellidos(String apellidos) { this.apellidos = apellidos; }

    // Correo
    public String getCorreo() { return gmail; }
    public void setCorreo(String correo) { this.gmail = correo; }

    // Contraseña
    public String getContrasenya() { return password; }
    public void setContrasenya(String contrasenya) { this.password = contrasenya; }

    // Activo
    public String getActivo() { return activo; }
    public void setActivo(String activo) { this.activo = activo; }

    // Acción
    public String getAction() { return accion; }
    public void setAction(String action) { this.accion = action; }
}
