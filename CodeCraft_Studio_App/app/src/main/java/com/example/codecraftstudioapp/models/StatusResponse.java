package com.example.codecraftstudioapp.models;

public class StatusResponse {
    private boolean success;
    private String statusCode;
    private String statusMessage;
    private String status_pembayaran;

    public boolean isSuccess() { return success; }
    public String getStatusCode() { return statusCode; }
    public String getStatusMessage() { return statusMessage; }
    public String getStatusPembayaran() { return status_pembayaran; }
}
