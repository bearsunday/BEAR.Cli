# typed: false
# frozen_string_literal: true

class Hellocli < Formula
  desc "CLI commands for MyVendor.HelloCli"
  homepage "https://github.com/koriym/MyVendor.HelloCli"
  head "https://github.com/koriym/MyVendor.HelloCli.git", branch: "1.x"
  license "MIT"

  depends_on "php"
  depends_on "composer"

  def install
    # アプリケーション全体をlibexecにインストール
    libexec.install Dir["*"]

    # libexecディレクトリに移動して作業
    cd libexec do
      # 依存関係のインストール
      system "composer", "install", "--prefer-dist"

      # CLIコマンドの生成
      system "#{libexec}/vendor/bear/cli/bin/bear-cli-gen", "MyVendor\\HelloCli"
    end

    # binディレクトリを作成
    bin.mkpath

    # helloコマンドをbinに移動
    target_command = "hello"
    if File.exist?("#{libexec}/bin/#{target_command}")
      mv "#{libexec}/bin/#{target_command}", bin/target_command
      chmod 0755, bin/target_command
    end
  end

  test do
    assert_match "Usage:", shell_output("#{bin}/hello --help")
  end
end
