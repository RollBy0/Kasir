
<?php 
include "header.php";
?>
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
      <!-- Info boxes -->
      <div class="row">
        <div class="col-md-4">
      <div class="box box-primary">
            <div class="box-body">
              <!-- Form Filter Tanggal Laporan -->
                <form action="transaksi_laporan.php" method="$_GET">
              <table class="table table-bordered table-striped">
                <tr>
                    <td>
                        <label>Tanggal Awal</label>
                        <input type="date" class="form-control" name="tgl_awal">
                    </td>
                    <td>
                        <label>Tanggal Akhir</label>
                        <input type="date" class="form-control" name="tgl_akhir">
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" class="btn btn-primary pull-right" value="Filter">
                    </td>
                    </tr>
              </table>
                </form>
                <!-- Form Filter Tanggal Laporan -->
            </div>
            <!-- /.box-body -->
          </div>
        </div>
        <?php 
        //Mengambil tanggal filter dari Tgl_Awal dan Tgl_Akhir
        if(isset($_GET['tgl_awal']) && isset($_GET['tgl_akhir'])){
            $Awal = $_GET['tgl_awal'];
            $Akhir = $_GET['tgl_akhir'];
    
        ?>
        <div class="col-md-8">
      <div class="box box-primary">
        <div class="box-header">
          <!-- Tombol Cetak -->
            <a href="transaksi_laporan_cetak.php?dari=<?php echo $Awal ?>&sampai=<?php echo $Akhir?>" target="_blank" class="btn btn-success pull-right"><i class="glyphicon glyphicon-print"> Cetak</i></a>
          <!-- Tombol Cetak -->  
          </div>
            <div class="box-body">
              <table class="table table-bordered table-striped">
                <thead>
                    <th>NO</th>
                    <th>NOMOR TRANSAKSI</th>
                    <th>TANGGAL PENJUALAN</th>
                    <th>NAMA PELANGGAN</th>
                    <th>NAMA PETUGAS</th>
                    <th>TOTAL</th>
                </thead>
                <tbody>
                  <!-- Table Laporan Penjualan sesuai filter Tanggal-->
                    <?php 
                    $UserID=$_SESSION['UserID'];
                    $dt_penjualan = mysqli_query($koneksi,"SELECT * from tb_penjualan 
                    INNER JOIN tb_pelanggan ON tb_pelanggan.PelangganID = tb_penjualan.PelangganID 
                    INNER JOIN tb_user ON tb_user.UserID = tb_penjualan.UserID WHERE date(TanggalPenjualan) >='$Awal' && (TanggalPenjualan) <='$Akhir'
                    Order BY TanggalPenjualan DESC"  );
                    $no=1;
                    while($penjualan=mysqli_fetch_array($dt_penjualan)){

                    ?>
                    <tr>
                    <td><?php echo $no++ ?></td>
                    <td><?php echo $penjualan['PenjualanID'] ?></td>
                    <td><?php echo $penjualan['TanggalPenjualan'] ?></td>
                    <td><?php echo $penjualan['NamaPelanggan']?></td>
                    <td><?php echo $penjualan['NamaUser'] ?></td>
                    <td><?php echo "Rp.".number_format($penjualan['TotalHarga']).",-" ?></td>
                    </tr>
                    <?php 
                    }
                    ?>
                    <!-- Table Laporan Penjualan sesuai filter Tanggal-->
                </tbody>
              </table>
            </div>
            <!-- /.box-body -->
          </div>
        </div>
        <?php 
        
        }?>
      </div>
                
          <!-- /.box -->
    </section>
    <!-- /.content -->
</div>
  <!-- /.content-wrapper -->
<?php 
include "footer.php";
?>
