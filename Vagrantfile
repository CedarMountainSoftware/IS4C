# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # database server
  config.vm.define "db" do |db|
    db.vm.hostname = "db.mcfc.local"
    db.vm.box = "precise32"
    db.vm.box_url = "http://files.vagrantup.com/precise32.box"
    db.vm.network :private_network, ip: "33.33.33.10"

    # shell configuration
    # first set of installs are for dev work and debugging
    config.vm.provision :shell do |sh|
      sh.inline = <<-EOF
        sudo apt-get update
        sudo apt-get upgrade
        sudo apt-get install -y git tmux mysql-client
        sudo apt-get install -y mysql-server apache2 php5 libapache2-mod-php5 python-mysqldb php5-mysql php-image-barcode php-fpdf
        mkdir ~/src
        cd ~/src
        git clone https://github.com/Missoula-Food-Coop/IS4C.git
        sudo ln -s /home/vagrant/src/IS4C/pos/ /pos
      EOF
      # here's where server setup and db population scripts go, once they're written...
    end
  end

  # database server
  config.vm.define "lane_1" do |lane_1|
    lane_1.vm.hostname = "lane-1.mcfc.local"
    lane_1.vm.box = "precise32"
    lane_1.vm.box_url = "http://files.vagrantup.com/precise32.box"
    lane_1.vm.network :private_network, ip: "33.33.33.11"

    # shell configuration
    config.vm.provision :shell do |sh|
      sh.inline = <<-EOF
        sudo apt-get update
        sudo apt-get upgrade
        sudo apt-get install git
        mkdir ~/src
        cd ~/src
        git clone https://github.com/Missoula-Food-Coop/IS4C.git
        sudo ln -s /home/vagrant/src/IS4C/pos/ /pos
      EOF
    end
  end

  # database server
  config.vm.define "lane_2" do |lane_2|
    lane_2.vm.hostname = "lane-2.mcfc.local"
    lane_2.vm.box = "precise32"
    lane_2.vm.box_url = "http://files.vagrantup.com/precise32.box"
    lane_2.vm.network :private_network, ip: "33.33.33.12"

    # shell configuration
    config.vm.provision :shell do |sh|
      sh.inline = <<-EOF
        sudo apt-get update
        sudo apt-get upgrade
        sudo apt-get install git
        mkdir ~/src
        cd ~/src
        git clone https://github.com/Missoula-Food-Coop/IS4C.git
        sudo ln -s /home/vagrant/src/IS4C/pos/ /pos
      EOF
    end
  end
#  config.vm.provision "ansible" do |ansible|
#    ansible.playbook = "ansible/setup_server.yml"
#  end
  # shell configuration
#  config.vm.provision :shell do |sh|
#    sh.inline = <<-EOF
#      sudo apt-get update
#      sudo apt-get install gcc g++ python-dev freetds-dev unixodbc-dev tdsodbc python-pip
#      sudo apt-get install postgresql-9.1 postgresql-server-dev-9.1
#      sudo apt-get install nginx
#    EOF
#  end
end
