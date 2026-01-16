package com.example.grupo5.aitherapp.pojos;

import com.google.gson.annotations.SerializedName;

public class PojoMedicionMapa {

    // Coincide con la columna 'valor' de tu BBDD
    @SerializedName("valor")
    private double valor;

    // Coincide con la columna 'medida' de la tabla tipo_medicion (Ej: "Temperatura")
    @SerializedName("medida")
    private String medida;

    // Coincide con la columna 'localizacion' (Viene como string "lat,lon")
    @SerializedName("localizacion")
    private String localizacion;

    public double getValor() { return valor; }
    public String getMedida() { return medida; }
    public String getLocalizacion() { return localizacion; }
}