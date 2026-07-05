package com.mycompany.uas_kas_harian;

import java.sql.*;
import javax.swing.JOptionPane;
import javax.swing.table.DefaultTableModel;

public class tampilTransaksiFrame extends javax.swing.JFrame {

    public tampilTransaksiFrame() {
        initComponents();
        setLocationRelativeTo(null);
        loadData();
    }

    private void loadData() {
        DefaultTableModel model = new DefaultTableModel(new String[]{"ID", "Tanggal", "Jenis", "Kategori", "Dompet", "Jumlah", "Keterangan"}, 0);
        
        int totalPemasukan = 0;
        int totalPengeluaran = 0;
        
        try {
            Connection conn = Koneksi.getKoneksi();
            String query = "SELECT t.id_transaksi, t.tanggal, t.jenis, k.nama_kategori, d.nama_dompet, t.jumlah, t.keterangan " +
                           "FROM transaksi t " +
                           "LEFT JOIN kategori k ON t.id_kategori = k.id_kategori " +
                           "LEFT JOIN dompet d ON t.id_dompet = d.id_dompet ORDER BY t.id_transaksi DESC";
            ResultSet rs = conn.createStatement().executeQuery(query);
            
            while(rs.next()) {
                int jumlah = rs.getInt("jumlah");
                String jenis = rs.getString("jenis");
                
                // Kalkulasi sisa saldo harian otomatis dari database
                if (jenis.equalsIgnoreCase("Pemasukan")) {
                    totalPemasukan += jumlah;
                } else {
                    totalPengeluaran += jumlah;
                }
                
                model.addRow(new Object[]{
                    rs.getInt("id_transaksi"), 
                    rs.getDate("tanggal"), 
                    jenis,
                    rs.getString("nama_kategori"), 
                    rs.getString("nama_dompet"), 
                    jumlah, 
                    rs.getString("keterangan")
                });
            }
            tableTransaksi.setModel(model);
            
            int sisaSaldo = totalPemasukan - totalPengeluaran;
            lblTotalSaldo.setText("Sisa Saldo Total: Rp " + sisaSaldo);
            
        } catch (SQLException e) { 
            System.out.println("Error memuat data transaksi: " + e.getMessage()); 
        }
    }                                        

                                      
    @SuppressWarnings("unchecked")
    // <editor-fold defaultstate="collapsed" desc="Generated Code">//GEN-BEGIN:initComponents
    private void initComponents() {

        jScrollPane1 = new javax.swing.JScrollPane();
        tableTransaksi = new javax.swing.JTable();
        btnTambah = new javax.swing.JButton();
        btnKembali = new javax.swing.JButton();
        jLabel1 = new javax.swing.JLabel();
        lblTotalSaldo = new javax.swing.JLabel();
        btnHapus = new javax.swing.JButton();

        setDefaultCloseOperation(javax.swing.WindowConstants.EXIT_ON_CLOSE);

        tableTransaksi.setModel(new javax.swing.table.DefaultTableModel(
            new Object [][] {
                {null, null, null, null},
                {null, null, null, null},
                {null, null, null, null},
                {null, null, null, null}
            },
            new String [] {
                "Title 1", "Title 2", "Title 3", "Title 4"
            }
        ));
        jScrollPane1.setViewportView(tableTransaksi);

        btnTambah.setText("Tambah");
        btnTambah.addActionListener(this::btnTambahActionPerformed);

        btnKembali.setText("Kembali");
        btnKembali.addActionListener(this::btnKembaliActionPerformed);

        jLabel1.setText("Table Transaksi Pengeluaran dan Pemasukan");

        lblTotalSaldo.setText("Total Saldo :   ");

        btnHapus.setText("Hapus");
        btnHapus.addActionListener(this::btnHapusActionPerformed);

        javax.swing.GroupLayout layout = new javax.swing.GroupLayout(getContentPane());
        getContentPane().setLayout(layout);
        layout.setHorizontalGroup(
            layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
            .addGroup(layout.createSequentialGroup()
                .addGroup(layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
                    .addGroup(layout.createSequentialGroup()
                        .addGroup(layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
                            .addGroup(layout.createSequentialGroup()
                                .addGap(155, 155, 155)
                                .addComponent(jLabel1))
                            .addGroup(layout.createSequentialGroup()
                                .addGap(150, 150, 150)
                                .addComponent(btnTambah)
                                .addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.RELATED)
                                .addComponent(btnHapus)
                                .addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.RELATED)
                                .addComponent(btnKembali)))
                        .addGap(0, 0, Short.MAX_VALUE))
                    .addGroup(layout.createSequentialGroup()
                        .addContainerGap()
                        .addGroup(layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
                            .addGroup(layout.createSequentialGroup()
                                .addGap(6, 6, 6)
                                .addComponent(lblTotalSaldo)
                                .addGap(0, 0, Short.MAX_VALUE))
                            .addComponent(jScrollPane1, javax.swing.GroupLayout.DEFAULT_SIZE, 539, Short.MAX_VALUE))))
                .addContainerGap())
        );
        layout.setVerticalGroup(
            layout.createParallelGroup(javax.swing.GroupLayout.Alignment.LEADING)
            .addGroup(layout.createSequentialGroup()
                .addGap(8, 8, 8)
                .addComponent(jLabel1)
                .addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.UNRELATED)
                .addComponent(jScrollPane1, javax.swing.GroupLayout.PREFERRED_SIZE, 140, javax.swing.GroupLayout.PREFERRED_SIZE)
                .addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.RELATED)
                .addComponent(lblTotalSaldo)
                .addPreferredGap(javax.swing.LayoutStyle.ComponentPlacement.RELATED, 8, Short.MAX_VALUE)
                .addGroup(layout.createParallelGroup(javax.swing.GroupLayout.Alignment.BASELINE)
                    .addComponent(btnHapus)
                    .addComponent(btnTambah)
                    .addComponent(btnKembali))
                .addGap(14, 14, 14))
        );

        pack();
    }// </editor-fold>//GEN-END:initComponents

    private void btnKembaliActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnKembaliActionPerformed
        new MenuUtama().setVisible(true);
        this.dispose();       
    }//GEN-LAST:event_btnKembaliActionPerformed

    private void btnTambahActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnTambahActionPerformed
        new tambahTransaksiFrame().setVisible(true);
        this.dispose();       
    }//GEN-LAST:event_btnTambahActionPerformed

    private void btnHapusActionPerformed(java.awt.event.ActionEvent evt) {//GEN-FIRST:event_btnHapusActionPerformed
        int barisTerpilih = tableTransaksi.getSelectedRow();
        
        if (barisTerpilih == -1) {
            JOptionPane.showMessageDialog(this, "Silakan pilih data riwayat kas yang ingin dihapus terlebih dahulu!");
            return;
        }
        
        int konfirmasi = JOptionPane.showConfirmDialog(this, 
                "Apakah Anda yakin ingin menghapus catatan transaksi terpilih?", 
                "Konfirmasi Hapus Data", 
                JOptionPane.YES_NO_OPTION);
                
        if (konfirmasi == JOptionPane.YES_OPTION) {
            try {
                int idTransaksi = Integer.parseInt(tableTransaksi.getValueAt(barisTerpilih, 0).toString());
                
                Connection conn = Koneksi.getKoneksi();
                String sql = "DELETE FROM transaksi WHERE id_transaksi = ?";
                PreparedStatement ps = conn.prepareStatement(sql);
                ps.setInt(1, idTransaksi);
                
                ps.executeUpdate();
                JOptionPane.showMessageDialog(this, "Catatan pengeluaran/pemasukan berhasil dihapus!");
                
                loadData(); 
                
            } catch (SQLException e) { 
                JOptionPane.showMessageDialog(this, "Gagal menghapus data dari database: " + e.getMessage()); 
            }
        }
    
    }//GEN-LAST:event_btnHapusActionPerformed

    /**
     * @param args the command line arguments
     */
    public static void main(String args[]) {
     
    }

    // Variables declaration - do not modify//GEN-BEGIN:variables
    private javax.swing.JButton btnHapus;
    private javax.swing.JButton btnKembali;
    private javax.swing.JButton btnTambah;
    private javax.swing.JLabel jLabel1;
    private javax.swing.JScrollPane jScrollPane1;
    private javax.swing.JLabel lblTotalSaldo;
    private javax.swing.JTable tableTransaksi;
    // End of variables declaration//GEN-END:variables
}
