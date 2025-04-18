#! /usr/bin/env python3
"""
This is a Bootstrap script for wrapper.py, in order to retain compatibility with earlier LibreNMS setups
"""

import logging
import os
import sys
from argparse import ArgumentParser

import LibreNMS
import LibreNMS.wrapper as wrapper

WRAPPER_TYPE = "discovery"
DEFAULT_WORKERS = 1

"""
    Take the amount of threads we want to run in parallel from the commandline
    if None are given or the argument was garbage, fall back to default
"""
usage = (
    "usage: %(prog)s [options] <amount_of_workers> (Default: {}"
    "(Do not set too high, or you will get an OOM)".format(DEFAULT_WORKERS)
)
description = "Spawn multiple discovery.php processes in parallel."
parser = ArgumentParser(usage=usage, description=description)
parser.add_argument(dest="amount_of_workers", default=DEFAULT_WORKERS)
parser.add_argument(
    "-d",
    "--debug",
    dest="debug",
    action="store_true",
    default=False,
    help="Enable debug output. WARNING: Leaving this enabled will consume a lot of disk space.",
)
parser.add_argument(
    "-m",
    "--modules",
    dest="modules",
    default="",
    help="Enable passing of a module string, modules are separated by comma",
)
args = parser.parse_args()

config = LibreNMS.get_config_data(os.path.dirname(os.path.realpath(__file__)))
if not config:
    logger = logging.getLogger(__name__)
    logger.critical("Could not run {} wrapper. Missing config".format(WRAPPER_TYPE))
    sys.exit(1)
log_dir = config["log_dir"]
log_file = os.path.join(log_dir, WRAPPER_TYPE + "_wrapper.log")
logger = LibreNMS.logger_get_logger(log_file, debug=args.debug)

scheduler = config.get("schedule_type").get("discovery", "legacy")
enabled = True if scheduler == "legacy" else scheduler == "cron"
if not enabled:
    logger.debug("Discovery is not enabled for cron scheduling")
    sys.exit(0)

try:
    amount_of_workers = int(args.amount_of_workers)
except (IndexError, ValueError):
    amount_of_workers = DEFAULT_WORKERS
    logger.warning(
        "Bogus number of workers given. Using default number ({}) of workers.".format(
            amount_of_workers
        )
    )

wrapper.wrapper(
    WRAPPER_TYPE,
    amount_of_workers=amount_of_workers,
    config=config,
    log_dir=log_dir,
    modules=args.modules or "",
    _debug=args.debug,
)
